<?php namespace OnePilot;

class Errors
{

    private $levelsLabels;
    private $intervals;

    public function __construct()
    {
        $this->intervals = [
            1 * 24 * 60,
            7 * 24 * 60,
            30 * 24 * 60,
            ];

        $this->levelsLabels = [
            1 => 'Info',
            2 => 'Warning',
            3 => 'Error',
            4 => 'Danger'
        ];
    }

    /**
     * Return the log activity of the last day,week,month by Level
     * @return array
     */
    public function overview()
    {
        $overview = array();
        foreach ($this->intervals as $interval) {

            $overview[$interval] = $this->last($interval);
        }

        return $overview;
    }

    private function last($minutes)
    {
        $dateToday = date('Y-m-d H:i:s', time() - ($minutes * 60));

        $sql = new \DbQuery();
        $sql->select('severity,count(*) as count');
        $sql->from('log', 'l');
        $sql->where("date_add >  '$dateToday'");
        $sql->groupBy('severity');

        $results = \Db::getInstance()->executeS($sql);

        $logs = array();
        foreach ($results as $result) {
            $logs[] = array(
                'level' => $this->levelsLabels[$result['severity']],
                'total' => (int)$result['count']
            );
        }

        return $logs;
    }
}