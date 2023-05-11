<?= $header;  ?>

<div class="main-content advanced-search-form">
	<?= $advanced_search ?>

	<div id="sidebar_wrapper">
		<?= $sidebar;  ?>
	</div>

	<div class="browse browse-title">
		<div class="browse-header-wrap">
			<h4 class="browse-header"></h4>

			<div class="sort-menu" id="sort_menu" style="display:none;">
				<p>Order by</p>
				<select class="js-sort-menu">
					<option value="alpha">Alphabetically</option>
					<option value="catalog_date">Release date</option>
				</select>
			</div><!-- end .sort-menu -->
		</div>

		<noscript>
			<p>Sorry, you need Javascript enabled to view these search results<p>
		</noscript>

		<ul class="browse-list"></ul>
		<div class="page-number-nav"></div>
	</div>
</div><!-- end .main-content -->

<?= $footer;  ?>

</body>

</html>
