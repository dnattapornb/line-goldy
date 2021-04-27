<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class Message
{
    /** @var string */
    private static $userId = '';
    /** @var string */
    private static $groupId = '';
    /** @var array */
    private static $mentionIds = [];
    /** @var string */
    private static $message = '';
    /** @var array */
    private static $groupName = [];
    /** @var array */
    private static $groupAction = [];
    /** @var bool */
    private $success = false;
    /** @var MessagePattern */
    private $pattern;

    public function __construct($userId, $groupId, $mentionIds, $message)
    {
        self::$userId = $userId;
        self::$groupId = $groupId;
        self::$mentionIds = $mentionIds;
        self::$message = $message;

        if (!$this->success) {
            $this->poemPattern();
        }

        if (!$this->success) {
            self::initGroupName();
            $this->bullPattern();
        }

        if (!$this->success) {
            self::initGroupAction();
            $this->lineCommandPattern();
        }
    }

    private static function initGroupName()
    {
        self::$groupName = [
            'boy'  => ['บอย'],
            'bas'  => ['บาส'],
            'poon' => ['ปูน'],
            'tong' => ['โต้ง'],
            'tee'  => ['ตี๋'],
            'pae'  => [
                'เป้',
                'เพ้',
            ],
            'krit' => [
                'กิต',
                'กิด',
                'กฤติน์',
                'กฤ',
                'กฤต',
            ],
            'att'  => [
                'อัต',
                'เต่า',
            ],
        ];
    }

    private static function getNamesFromGroupName()
    {
        $names = [];
        foreach (array_values(self::$groupName) as $value) {
            $names = array_merge($names, $value);
        }

        return $names;
    }

    private static function getCommandFromGroupName($value)
    {
        $command = '';
        foreach (self::$groupName as $key => $values) {
            if (in_array($value, $values)) {
                $command = $key;
                break;
            }
        }

        return $command;
    }

    private static function initGroupAction()
    {
        self::$groupAction = [
            'add'    => ['-a', '--add'],
            'modify' => ['-m', '--modify'],
            'list'   => ['-l', '--list'],
            // 'delete' => ['-d', '--delete'],
        ];
    }

    private static function getActionsFromGroupAction()
    {
        $actions = [];
        foreach (array_values(self::$groupAction) as $value) {
            $actions = array_merge($actions, $value);
        }

        return $actions;
    }

    private static function getActionFromGroupAction($value)
    {
        $action = '';
        foreach (self::$groupAction as $key => $values) {
            if (in_array($value, $values)) {
                $action = $key;
                break;
            }
        }

        return $action;
    }

    private function poemPattern()
    {
        $pattern = '/(ไ+ป+\s*){3,6}/i';
        preg_match($pattern, self::$message, $matches);
        if (!empty($matches)) {
            $this->success = true;

            $this->pattern = new MessagePattern();
            $this->pattern->setCategory('POEM');
            $this->pattern->setCommand('go');
        }
    }

    private function bullPattern()
    {
        $pattern = '/';
        $pattern .= '(ไอ|อัย|พี่|น้อง)';
        $pattern .= '(';
        $pattern .= implode('|', self::getNamesFromGroupName());
        $pattern .= ')';
        $pattern .= '/i';
        preg_match($pattern, self::$message, $matches);
        if (!empty($matches)) {
            $command = self::getCommandFromGroupName($matches[2] ?? '');

            $this->success = true;

            $this->pattern = new MessagePattern();
            $this->pattern->setCategory('BULL');
            $this->pattern->setCommand($command);
        }
    }

    private function lineCommandPattern()
    {
        $commands = ['user', 'char'];

        $pattern = '/';
        $pattern .= '^(';
        $pattern .= implode('|', $commands);
        $pattern .= ')';
        $pattern .= '.*?';
        $pattern .= '(';
        $pattern .= implode('|', self::getActionsFromGroupAction());
        $pattern .= ')';
        $pattern .= '/';
        preg_match($pattern, self::$message, $matches);
        if (!empty($matches)) {
            $command = $matches[1];
            $action = self::getActionFromGroupAction($matches[2]);

            $this->success = true;

            $this->pattern = new MessagePattern();
            $this->pattern->setCategory('LINE-COMMAND');
            $this->pattern->setCommand($command);
            $this->pattern->setAction($action);

            // parameters
            if (isset($command) && !empty($command) && isset($action) && !empty($action)) {
                $groupParameters = [
                    'key'  => ['-k', '--key'],
                    'name' => ['-n', '--name'],
                    'user' => ['-u', '--user'],
                ];

                foreach ($groupParameters as $key => $parameters) {
                    if ($key === 'key') {
                        $pattern = '/';
                        $pattern .= '(';
                        $pattern .= implode('|', $parameters);
                        $pattern .= ')';
                        $pattern .= '=(\d+)';
                        $pattern .= '/';
                        preg_match($pattern, self::$message, $matches);
                        if (!empty($matches)) {
                            /**
                             * array:3 [
                             * 0 => "-k=1234567890"
                             * 1 => "-k"
                             * 2 => "1234567890"
                             * ]
                             */
                            $parameter = new MessagePatternParameter();
                            $parameter->setKey($matches[1]);
                            $parameter->setValue(trim($matches[2]));
                            $this->pattern->addParameter($key, $parameter);
                        }
                    }

                    if ($key === 'name') {
                        $pattern = '/';
                        $pattern .= '(';
                        $pattern .= implode('|', $parameters);
                        $pattern .= ')';
                        $pattern .= '=([a-zA-Z0-9ก-๙\s_-]+)';
                        $pattern .= '/';
                        preg_match($pattern, self::$message, $matches);
                        if (!empty($matches)) {
                            /**
                             * array:3 [
                             * 0 => "-n=บอย BoY_i-i"
                             * 1 => "-n"
                             * 2 => "บอย BoY_i-i"
                             * ]
                             */
                            $parameter = new MessagePatternParameter();
                            $parameter->setKey($matches[1]);
                            $parameter->setValue(trim($matches[2]));
                            $this->pattern->addParameter($key, $parameter);
                        }
                    }

                    if ($key === 'user') {
                        $pattern = '/';
                        $pattern .= '(';
                        $pattern .= implode('|', $parameters);
                        $pattern .= ')';
                        $pattern .= '=([\@a-zA-Z0-9ก-๙\s_-]+)';
                        $pattern .= '/';
                        preg_match($pattern, self::$message, $matches);
                        if (!empty($matches)) {
                            /**
                             * array:3 [
                             * 0 => "-u=@вιя"
                             * 1 => "-u"
                             * 2 => "@вιя"
                             * ]
                             */
                            if (isset(self::$mentionIds) && !empty(self::$mentionIds)) {
                                $parameter = new MessagePatternParameter();
                                $parameter->setKey($matches[1]);
                                $parameter->setValue(self::$mentionIds);
                                $this->pattern->addParameter($key, $parameter);
                            }
                        }
                    }
                }
            }
        }
    }
}
