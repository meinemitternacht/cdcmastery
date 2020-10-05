<?php
declare(strict_types=1);

namespace CDCMastery\Models\Messages;

abstract class MessageTypes
{
    public const EMERGENCY = 'danger';
    public const ALERT = 'danger';
    public const CRITICAL = 'danger';
    public const ERROR = 'danger';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'info';
    public const SUCCESS = 'success';
}