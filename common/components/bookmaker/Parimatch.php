<?php
/**
 * Created by PhpStorm.
 * User: nnikitchenko
 * Date: 22.05.2016
 */

namespace common\components\bookmaker;


use common\components\bookmaker\_base\BaseBookmaker;
use common\components\bookmaker\_interface\iBookmakerEvent;
use common\components\bookmaker\_interface\iSport;
use common\components\bookmaker\parimatch\Event;
use common\components\bookmaker\parimatch\Sport;
use common\components\Phantom;
use common\helpers\SportHelper;
use common\models\Bookmaker;
use common\models\Team;
use common\models\TeamAlias;
use Doctrine\Common\Collections\ArrayCollection;
use simplehtmldom_1_5\simple_html_dom;
use simplehtmldom_1_5\simple_html_dom_node;
use common\components\bookmaker\parimatch\factories\ParserValidate;
use Yii;

class Parimatch extends BaseBookmaker
{
    protected $sport_link = [
        SportHelper::SPORT_FOOTBALL     => 'sport/futbol',
        SportHelper::SPORT_TENNIS       => 'sport/tennis',
        SportHelper::SPORT_BASKETBALL   => 'sport/basketbol',
        SportHelper::SPORT_HOKKEY       => 'sport/khokkejj',
    ];

    protected $options = [
        'delay' => 8,
        'referer' => 'http://google.com'
    ];

    public function __construct(Bookmaker $settings)
    {
        parent::__construct($settings);

        $this->connector
            ->setClient(Yii::$app->phantom)
            ->setDelay(8);
    }

    public function connect($alias = null, $proxy = null)
    {
        $this->connector
            ->setHost($alias ? $alias : $this->getAlias())
            ->setProxy($proxy)
            ->setReferer(null);

        $time_start = microtime(true);

        $response =  $this->getClient()->get($this->connector->getHost());
        if(preg_match('/Результаты live/ui', $response)) {
            $this->connector
                ->setIsConnect(true)
                ->setReferer($alias);
        }
        
        Yii::trace(sprintf('Тестовый коннект: %s сек. Результат: %d', microtime(true) - $time_start, $this->connector->isConnect() ? 1 : 0));

        return $this->connector->isConnect();
    }

    /**
     * @return Phantom
     */
    private function getClient()
    {
        return $this->connector->getClient();
    }

    /**
     * @param null $proxy
     * @return array
     */
    public function checkAll($proxy = null)
    {
        $alias_ids = [];

        $this->connector
            ->setProxy($proxy)
            ->setReferer(null);

        foreach ($this->getAliases() as $alias) {
            $response = $this->getClient()->get($alias);
            if(preg_match('/Результаты live/ui', $response)) {
                $alias_ids[] = $alias;
            }
        }

        return $alias_ids;
    }

    public function getSportList($sport_type)
    {
        $returned = [];

        if(!isset($this->sport_link[$sport_type])) {
            return $returned;
        }

        $response = $this->getClient()
            ->get(sprintf('%s/%s', rtrim($this->connector->getHost(), '/'), ltrim($this->sport_link[$sport_type], '/')));

        /** @var simple_html_dom $dom */
        $dom = \Sunra\PhpSimple\HtmlDomParser::str_get_html('<html>'.$response.'</html>');
        /** @var simple_html_dom_node $sport_el */
        foreach ($dom->find('ul[id=sports] li a') as $sport_el) {
            $title = $sport_el->text();
            if(preg_match('/'.$this->settings->ignore_sport_regexp.'/ui', $title)) {
                continue;
            }

            $Sport = $this->getSport();
            $Sport
                ->setLink($sport_el->getAttribute('href'))
                ->setTitle($title)
                ->setSportType($sport_type);
            $returned[] = $Sport;
        }

        return $returned;
    }

    public function getSport()
    {
        return new Sport();
    }

    /**
     * @param iSport $Sport
     * @return iBookmakerEvent[]
     */
    public function getEvents($Sport)
    {
        $team_list = [];

        $events = new ArrayCollection();

        $time_start = microtime(true);
        $response = $this->getClient()
            ->get(sprintf('%s/%s', rtrim($this->connector->getHost(), '/'), ltrim($Sport->getLink(), '/')));
        \Yii::trace(sprintf('Получили HTML лиг: %s сек.', microtime(true) - $time_start));
        if(!$response) {
            return $events;
        }

        $time_start = microtime(true);
        $Validator = ParserValidate::getValidator($Sport->getSportType(), '<html>'.$response.'</html>');
        if($Validator === false) {
            return $events;
        }
        \Yii::trace(sprintf('Определили парсер: %s сек.', microtime(true) - $time_start));

        $time_start = microtime(true);
        $items = $Validator->getParser()->run()->getEvents();
        \Yii::trace(sprintf('Распарсили: %s сек.', microtime(true) - $time_start));

        $template_name = $Validator->getTemplateName();
        unset($Validator);

        $Sport->setTemplate($template_name);
        foreach ($items as $item) {
            if($this->settings->ignore_event_regexp && preg_match('/'.$this->settings->ignore_event_regexp.'/ui', $item['team_1'])) {
                continue;
            }
            if($this->settings->ignore_event_regexp && preg_match('/'.$this->settings->ignore_event_regexp.'/ui', $item['team_2'])) {
                continue;
            }

            $Event = new Event();
            $Event->setDate($item['date_string'])
                ->setTeam1($item['team_1'])
                ->setTeam2($item['team_2'])
                ->setOdds($item['ratio_list']);

            if($Event->getDate() < time()) {
                continue;
            }

            if(!$events->contains($Event)) {
                $events->add($Event);


                foreach (['team_1', 'team_2'] as $team) {
                    if(!in_array($item[$team], $team_list)) {
                        $team_list[] = $item[$team];
                    }
                }
            }
        }

        $AliasList = TeamAlias::find()
            ->indexBy('title')
            ->andWhere(['in', 'title', $team_list])
            ->all();
        /** @var Event $event */
        foreach ($events as $event) {
            $team_1 = $event->getTeam1();
            $team_2 = $event->getTeam2();

            $TeamAlias1 = isset($AliasList[$team_1]) ? $AliasList[$team_1] : $this->createTeam($team_1);
            $TeamAlias2 = isset($AliasList[$team_2]) ? $AliasList[$team_2] : $this->createTeam($team_2);

            $event->setTeam1Alias($TeamAlias1);
            $event->setTeam2Alias($TeamAlias2);
        }

        return $events;
    }

    private function createTeam($team)
    {
        $Team = new Team();
        $Team->title = $team;
        $Team->save();

        $TeamAlias = new TeamAlias();
        $TeamAlias->team_id = $Team->id;
        $TeamAlias->title = $team;
        $TeamAlias->save();

        return $TeamAlias;
    }
}