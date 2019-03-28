<?php namespace OnePilot;

class Errors
{
    /** @var array intervals in minutes */
    const LEVELS = array(
        1 => 'info',
        2 => 'warning',
        3 => 'error',
        4 => 'danger'
    );

    const INTERVALS = array(
        1 * 24 * 60,
        7 * 24 * 60,
        30 * 24 * 60,
    );

    /**
     * Return the log activity of the last day,week,month by Level
     * @return array
     */
    public function overview()
    {
        $overview = array();
        foreach (self::INTERVALS as $interval) {

            $overview[$interval] = $this->last($interval);
        }

        return $overview;
    }

    private function last($minutes)
    {
        $muchPast = time() - ($minutes * 60);
        $dateToday = date('Y-m-d H:i:s', $muchPast);

        $sql = new \DbQuery();
        $sql->select('severity,count(*) as count');
        $sql->from('log', 'l');
        $sql->where("date_add >  '$dateToday'");
        $sql->groupBy('severity');

        $results = \Db::getInstance()->executeS($sql);

        $logs = array();
        foreach ($results as $result) {
            $logs[] = array(
                'level' => self::LEVELS[$result['severity']],
                'total' => (int)$result['count']
            );
        }

        return $logs;
    }
}