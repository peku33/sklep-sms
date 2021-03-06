<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

$heart->register_page("tariffs", "PageAdminTariffs", "admin");

class PageAdminTariffs extends PageAdmin implements IPageAdmin_ActionBox
{
    const PAGE_ID = "tariffs";
    protected $privilage = "manage_settings";

    function __construct()
    {
        global $lang;
        $this->title = $lang->translate('tariffs');

        parent::__construct();
    }

    protected function content($get, $post)
    {
        global $heart, $settings, $lang;

        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($lang->translate('tariff'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($lang->translate('provision')));

        foreach ($heart->getTariffs() as $tariff) {
            $body_row = new BodyRow();

            $provision = number_format($tariff->getProvision() / 100.0, 2);

            $body_row->setDbId($tariff->getId());
            $body_row->addCell(new Cell("{$provision} {$settings['currency']}"));

            $body_row->setButtonEdit(true);
            if (!$tariff->isPredefined()) {
                $body_row->setButtonDelete(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        $button = new Input();
        $button->setParam('id', 'tariff_button_add');
        $button->setParam('type', 'button');
        $button->setParam('value', $lang->translate('add_tariff'));
        $wrapper->addButton($button);

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        global $heart, $lang, $settings, $templates; // settings potrzebne

        if (!get_privilages("manage_settings")) {
            return [
                'status' => "not_logged_in",
                'text'   => $lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($box_id) {
            case "tariff_add":
                $output = eval($templates->render("admin/action_boxes/tariff_add"));
                break;

            case "tariff_edit":
                $tariff = $heart->getTariff($data['id']);
                $provision = number_format($tariff->getProvision() / 100.0, 2);

                $output = eval($templates->render("admin/action_boxes/tariff_edit"));
                break;
        }

        return [
            'status'   => 'ok',
            'template' => $output,
        ];
    }
}