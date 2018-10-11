<?php
class ControllerStartupError extends Controller {
	public function index() {
		$this->registry->set('log', new Log($this->config->get('config_error_filename')));
		
		set_error_handler(array($this, 'handler'));
		set_exception_handler(array($this, 'exception_handler'));
	}

    public function exception_handler($exception)
    {
        if (!is_object($exception)) {
            return;
        }
        if ($this->config->get('config_error_log')) {
            $this->logger = new Log('exception.log');
            $this->logger->writeException($exception);
        }
        if ($this->whoops && is_debug()) {
            $this->whoops->handleException($exception);
        }
        if (defined('SEND_BEARYCHAT') && SEND_BEARYCHAT == true) {
            $message = 'PHP Exception: ' . $exception->getCode() . ', ' . $exception->getMessage() . $exception->getFile() . "(line:{$exception->getLine()})";
            BearyChat::getInstance($this->registry)->send($message);
        }
        if ($this->config->get('config_error_display')) {
            throw $exception;
        }
    }
	
	public function handler($code, $message, $file, $line) {
		// error suppressed with @
		if (error_reporting() === 0) {
			return false;
		}
	
		switch ($code) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$error = 'Notice';
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$error = 'Warning';
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$error = 'Fatal Error';
				break;
			default:
				$error = 'Unknown';
				break;
		}
	
		if ($this->config->get('config_error_display')) {
			echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';
		}
	
		if ($this->config->get('config_error_log')) {
			$this->log->write('PHP ' . $error . ':  ' . $message . ' in ' . $file . ' on line ' . $line);
		}

        if (defined('SEND_BEARYCHAT') && SEND_BEARYCHAT == true) {
            $message = 'PHP ' . $error . ':  ' . $message . ' in ' . $file . ' on line ' . $line;
            BearyChat::getInstance($this->registry)->send($message);
        }
        if ($this->whoops && is_debug()) {
            throw new \ErrorException($message, $code, 0, $file, $line);
        }
	
		return true;
	} 
} 