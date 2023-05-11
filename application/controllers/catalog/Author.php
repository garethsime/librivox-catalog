<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Author extends Catalog_controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('general_functions_helper');
	}

	public function index($author_id)
	{
		$this->load->model('author_model');
		$this->data['author'] = $this->author_model->get($author_id);

		$this->data['search_category'] = 'author';

		$this->data['primary_key'] = $author_id;

		[
			'total_matches' => $this->data['total_matches'],
			'matches' => $this->data['matches'],
			'pagination' => $this->data['pagination'],
		] = $this->_load_results_and_pagination(
			$author_id,
			$this->input->get('project_type') ?? 'either',
			$this->input->get('search_order') ?? 'alpha',
			$this->input->get('search_page') ?? 1,
		);

		$this->data['search_order'] = $this->input->get('search_order');

		$this->_render('catalog/author');
		return;
	}

	function get_results()
	{
		//collect - search_category, sub_category, page_number, sort_order
		$input = $this->input->get(null, true);
		$author_id = $input['primary_key'];
		$search_order = $input['search_order'];

		[
			'matches' => $retval['results'],
			'pagination' => $retval['pagination'],
		] = $this->_load_results_and_pagination(
			$author_id,
			$input['project_type'] ?? 'either',
			$search_order ?? 'alpha',
			$input['search_page'] ?? 1,
		);

		$retval['status'] = 'SUCCESS';

		//return - results, pagination
		if ($this->input->is_ajax_request())
		{
			header('Content-Type: application/json;charset=utf-8');
			echo json_encode($retval);
			return;
		}
	}

	/**
	 * Generates HTML for matches and the corresponding pagination.
	 *
	 * @param string $author_id The ID of the author
	 * @param string $project_type Which types of project may be returned
	 * @param string $search_order The order of the search results
	 * @param int $search_page The page of results we're loading
	 * @return array The total matches, match HTML, and pagination HTML
	 */
	private function _load_results_and_pagination(
		string $author_id,
		string $project_type,
		string $search_order,
		int $search_page,
	) {
		// Grab all the matches
		$offset = ($search_page - 1) * CATALOG_RESULT_COUNT;
		$matches = $this->_get_all_author(
			$author_id,
			$offset,
			CATALOG_RESULT_COUNT,
			$search_order,
			$project_type,
		);
		$formatted_matches = $this->_format_results($matches, 'title');

		// Grab the total number of results, used for pagination
		$total_matches = count($this->_get_all_author($author_id, project_type: $project_type));

		// Grab the corresponding pagination
		$page_count = ($total_matches > CATALOG_RESULT_COUNT)
			? ceil($total_matches / CATALOG_RESULT_COUNT)
			: 0;

		$pagination = (empty($page_count))
			? ''
			: $this->_format_pagination(
				$search_page,
				$page_count,
				'get_results',
				function ($page) use ($author_id) {
					return '/author/' . $author_id . '/?search_page=' . $page;
				},
			);

		return [
			'total_matches' => $total_matches,
			'matches' => $formatted_matches,
			'pagination' => $pagination,
		];
	}

	function _get_all_author($author_id, $offset = 0, $limit = 1000000, $search_order = 'alpha', $project_type = 'either')
	{
		$params['author_id'] = $author_id;
		$params['offset'] = $offset;
		$params['limit'] = $limit;
		$params['search_order'] = $search_order;
		$params['project_type'] = $project_type;

		$this->load->model('project_model', 'model');
		$projects = $this->model->get_projects_by_author($params);

		//echo $this->db->last_query();

		$this->load->model('author_model');
		$this->load->model('section_model');

		foreach ($projects as $key => $project)
		{
			if ($project['primary_type'] == 'section')
			{
				$section = $this->section_model->get($project['primary_key']);

				if (empty($section) || !isset($section->author_id))
				{
					$projects[$key]['author_list'] = 'n/a';
					continue;
				}

				//echo $section->author_id. '::';

				$authors = $this->author_model->get_author_list($section->author_id);
				if (empty($authors))
				{
					$projects[$key]['author_list'] = 'n/a';
				}
				else
				{
					$authors = array_slice($authors, 0, 20); //only show 20 authors on page
					$projects[$key]['author_list'] = $this->_authors_string($authors);
				}
			}
			else
			{
				$authors = $this->author_model->get_author_list_by_project($project['primary_key'], 'author');
				if (empty($authors))
				{
					$projects[$key]['author_list'] = 'n/a';
				}
				else
				{
					$authors = array_slice($authors, 0, 20); //only show 20 authors on page
					$projects[$key]['author_list'] = $this->_authors_string($authors);
				}
			}
		}

		return $projects;
	}
}
