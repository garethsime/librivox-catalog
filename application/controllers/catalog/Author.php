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

		$all_matches = $this->_get_all_author($author_id);
		$this->data['matches'] = count($all_matches);

		// Pre-load the first page of results, this saves the browser having to
		// make a second request. I'm conflicted about this though. I think
		// there are two possible jobs-to-be-done when people land on the Author
		// page:
		//
		//  * "I want to know more about the Author"
		//  * "I want to see other works by the Author"
		//
		// If the point is to know more about the author, then this change is
		// bad -- it delays loading the profile while we fetch the first page of
		// results.
		//
		// If the point is to see other works by the author, then this change is
		// good -- we've elimitated a round-trip where we had to load the
		// profile and _then_ load the books by that author.

		$search_page = $this->input->get('search_page') ?? 1;
		$offset = ($search_page - 1) * CATALOG_RESULT_COUNT;
		$first_page_matches = $this->_get_all_author(
			$author_id,
			$offset,
			CATALOG_RESULT_COUNT,
		);
		$this->data['match_results'] = $this->_format_results($first_page_matches, 'title');

		$page_count = (count($all_matches) > CATALOG_RESULT_COUNT)
			? ceil(count($all_matches) / CATALOG_RESULT_COUNT)
			: 0;

		$this->data['pagination'] = (empty($page_count))
			? ''
			: $this->_format_pagination(
				$search_page,
				$page_count,
				'get_results',
				function ($page) use ($author_id) {
					return '/author/' . $author_id . '/?search_page=' . $page;
				},
			);

		$this->_render('catalog/author');
		return;
	}

	function get_results()
	{
		//collect - search_category, sub_category, page_number, sort_order
		$input = $this->input->get(null, true);

		//format offset
		$offset = ($input['search_page'] - 1) * CATALOG_RESULT_COUNT;

		// go get results
		$results = $this->_get_all_author($input['primary_key'], $offset, CATALOG_RESULT_COUNT, $input['search_order'], $input['project_type']);

		$full_set = $this->_get_all_author($input['primary_key'], 0, 1000000, 'alpha', $input['project_type']);
		//$retval['sql'] = $this->db->last_query();

		// go format results
		$retval['results'] = $this->_format_results($results, 'title');

		//pagination
		$page_count = (count($full_set) > CATALOG_RESULT_COUNT) ? ceil(count($full_set) / CATALOG_RESULT_COUNT) : 0;
		$retval['pagination'] = (empty($page_count)) ? '' : $this->_format_pagination($input['search_page'], $page_count);

		$retval['status'] = 'SUCCESS';

		//$retval['page_count'] = $page_count;
		//$retval['full_set'] = $full_set;

		//return - results, pagination
		if ($this->input->is_ajax_request())
		{
			header('Content-Type: application/json;charset=utf-8');
			echo json_encode($retval);
			return;
		}
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
