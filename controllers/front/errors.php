<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnePilot\Response;

class OnepilotErrorsModuleFrontController extends ModuleFrontController
{

    /** @var int */
    const PAGINATION = 20;

    public function init()
    {

        \OnePilot\Middlewares\Handler::register();
        \OnePilot\Middlewares\Authentication::register();

        parent::init();

        $from = Tools::getValue('from');
        $to = Tools::getValue('to');
        $levels = is_array($levels = Tools::getValue('levels')) ? $levels : null;
        $search = Tools::getValue('search');
        $page = Tools::getValue('page', 0);

        $sql = new \DbQuery();
        $sql->select('id_log as id, severity as level, message, date_add as date');
        $sql->from('log', 'l');

        if ($from) {
            $sql->where("date_add > $from");
        }
        if ($to) {
            $sql->where("date_add > $to");
        }
        if ($levels) {
            $sql->where("severity in (" . implode(',', $levels) . ")");
        }
        if ($search) {
            $sql->where("message like '%$search%'");
        }

        $sql->limit($this::PAGINATION, $page);
        $sql->orderBy('date_add desc');

        $results = \Db::getInstance()->executeS($sql);

        Response::make([
            'success' => true,
            'message' => $results,
        ]);
    }

}