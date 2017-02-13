<?php
namespace Vicky\client\modules\Jira;

use Vicky\client\exceptions\JiraWebhookException;
use League\Event\Emitter;

class JiraWebhook
{
    private static $converter = [];
    private static $emitter;
    protected $callbacks = [];
    private $rawData;
    private $data;

    /**
     * Set converter for formatting messages
     *
     * @param $name - convertor name
     * @param $converter - object that extend JiraWebhookDataConverter
     */
    public static function setConverter($name, JiraWebhookDataConverter $converter)
    {
        self::$converter[$name] = $converter;
    }

    /**
     * Converts $data into message (string) by converter
     *
     * @param $name - convertor name
     * @param $data - instance of the class JiraWebhookData
     *
     * @return mixed
     *
     * @throws JiraWebhookException
     */
    public static function convert($name, JiraWebhookData $data)
    {
        if (!empty(self::$converter[$name])) {
            return self::$converter[$name]->convert($data);
        } else {
            throw new JiraWebhookException("Converter {$name} is not registered!");
        }
    }

    /**
     * Initialize emitter
     *
     * @return Emitter
     */
    public static function getEmitter()
    {
        if (!self::$emitter) {
            self::$emitter = new Emitter();
        }

        return self::$emitter;
    }

    /**
     * Register listener for event
     *
     * @param $name - event name
     * @param $listener - listener (it could be function or object (see docs))
     * @param int $priority - listener priority
     */
    public function addListener($name, $listener, $priority = 0)
    {
        self::$emitter->addListener($name, $listener, $priority);
    }

    public function run()
    {
        $data = $this->extractData();

        if ($data->isBlocker()) {
            $this->on('priority.Blocker', $data);
        } else {

        }


        if ($data->isBlocker()) {
            $emitter->emit('type.Blocker', $data);


            $message = "!!! {$message}";
            //$this->toChannel('#general', $message);
            $this->toUser('chewbacca', $message);
        } elseif ($issueType === 'Operations') {
            if ($webhookEvent === 'jira:issue_created' || $status === 'Resolved') {
                $message = "⚙ {$message}";
                $this->toChannel('#general', $message);
            }
        } elseif ($issueType === 'Urgent bug') {
            if ($webhookEvent === 'jira:issue_created' || $status === 'Resolved' || $issueEvent === 'issue_commented') {
                $message = "⚡ {$message}";
                $this->toChannel('#general', $message);
            }
        }
    }

    /**
     * Get data from JIRA and parsing it
     *
     * @return JiraWebhookData - parsed data
     *
     * @throws JiraWebhookException
     */
    public function extractData()
    {
        $f = fopen('php://input', 'r');
        $data = stream_get_contents($f);

        if (!$data) {
            throw new JiraWebhookException('There is not data in the Jira webhook');
        }

        $this->rawData = json_decode($data, true);
        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE) {
            throw new JiraWebhookException("This data cannot be decoded from json (decode error: $jsonError)!");
        }

        $this->data = JiraWebhookData::parseWebhookData($this->rawData);

        return $this->data;
    }

    /**
     * Call listener by event name
     *
     * @param $name - event name
     * @param null $data
     */
    public function on($name, $data = null)
    {
        self::$emitter->emit($name, $data);
    }
}
