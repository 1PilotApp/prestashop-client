<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnePilot\Response;

class OnepilotErrorsModuleFrontController extends ModuleFrontController
{
    /** @var int */
    const PAGINATION = 20;

    /** @var array */
    const LEVELS = array(
        1 => 'info',
        2 => 'warning',
        3 => 'error',
        4 => 'danger'
    );

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
            $dateFrom = date('Y-m-d H:i:s', strtotime($from));
            $sql->where("date_add > '$dateFrom'");
        }

        if ($to) {
            $dateTo = date('Y-m-d H:i:s', strtotime($to));
            $sql->where("date_add < '$dateTo'");
        }

        if ($levels) {
            $levelsIds = array();
            foreach ($levels as $level) {
                $index = array_search($level, self::LEVELS);
                if ($index != false) {
                    $levelsIds[] = $index;
                }
            }

            $sql->where("severity in (" . implode(',', $levelsIds) . ")");
        }

        if ($search) {
            $sql->where("message like '%$search%'");
        }

        $sql->limit($this::PAGINATION, $page);
        $sql->orderBy('date_add desc');

        $results = \Db::getInstance()->executeS($sql);

        foreach ($results as &$result) {
            $index = $result['level'];
            $result['level'] = self::LEVELS[$index];
        }

        Response::make([
            'data' => $results,
        ]);
    }

}