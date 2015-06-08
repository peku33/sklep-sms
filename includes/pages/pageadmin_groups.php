<?php

$heart->register_page("groups", "PageAdminGroups", "admin");

class PageAdminGroups extends PageAdmin {

	protected $privilage = "view_groups";

	function __construct()
	{
		global $lang;
		$this->title = $lang['groups'];

		parent::__construct();
	}

	protected function content($get, $post) {
		global $db, $lang, $G_PAGE, $settings, $scripts;

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "groups` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;

			if (get_privilages("manage_groups")) {
				// Pobranie przycisku edycji
				$button_edit = create_dom_element("img", "", array(
					'id' => "edit_row_{$i}",
					'src' => "images/edit.png",
					'title' => $lang['edit']. " " . $row['name']
				));
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang['delete']. " " . $row['name']
				));
			} else
				$button_delete = $button_edit = "";

			$row['name'] = htmlspecialchars($row['name']);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/groups_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/groups_thead") . "\";");

		if (get_privilages("manage_groups"))
			// Pobranie przycisku dodającego grupę
			$buttons = create_dom_element("input", "", array(
				'id' => "button_add_group",
				'type' => "button",
				'value' => $lang['add_group']
			));

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/groups.js?version=" . VERSION;

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}