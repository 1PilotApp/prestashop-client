<?php namespace OnePilot;

class Errors
{

    private $levels;
    private $intervals;

    public function __construct()
    {
        $this->intervals = [
            1 * 24 * 60,
            7 * 24 * 60,
            30 * 24 * 60,
            ];

        $this->levels =  [
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
                'level' => $this->levels[$result['severity']],
                'total' => (int)$result['count']
            );
        }

        return $logs;
    }
}