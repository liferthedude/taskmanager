<?php

namespace Lifer\TaskManager\Support;
use Illuminate\Support\Facades\Log;

trait LoggingWithTags {

    protected $logging_tags = [];

    protected function logDebug($msg, $tags = []) {
      if (is_array($msg)) {
        $msg = json_encode($msg);
      }
      Log::debug($this->getTagsAsString($tags)." ".$msg);
    }

    protected function logWarning($msg, $tags = []) {
      Log::warning($this->getTagsAsString($tags)." ".$msg);
    }

    protected function logError($msg, $tags = []) {
      Log::error($this->getTagsAsString($tags)." ".$msg);
    }

    protected function _throwException($msg, $tags = []) {
      throw new \Exception($this->getTagsAsString($tags)." ".$msg);
    }

    protected function addTag($tag) {
      $this->logging_tags[] = $tag;
    }

    protected function getTagsAsString($tags) {

      $str = '';
      if (!empty($this->logging_tags)) {
        foreach ($this->logging_tags as $tag) {
          $str .= "[{$tag}] ";
        }
      }
      foreach ($tags as $tag) {
        $str .= "[{$tag}] ";
      }

      return trim($str);
    }
}