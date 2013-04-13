<?php namespace Awareness;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Contracts\MessageProviderInterface;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Validator;

/**
 * Aware Models
 *    Self-validating Eloquent Models
 */
abstract class Aware extends Model implements MessageProviderInterface
{

    /**
    * Error message container
    *
    * @var Illuminate\Support\MessageBag
    */

    protected $errorBag;

    /**
    * Aware Validation Messages
    *
    * @var array $messages
    */

    public static $messages = array();

    /**
    * Aware Validation Rules
    *
    * @var array $rules
    */
    public static $rules = array();

    /**
    * Returns the errors container
    *
    * @return Illuminate\Support\MessageBag
    */
    public function getErrors()
    {
        if (!$this->errorBag) {
            $this->errorBag = new MessageBag();
        }

        return $this->errorBag;
    }

    /**
    * Returns attirbutes with updated values
    *
    * @return array
    */
    public function getChanged()
    {
        return array_diff($this->attributes, $this->original);
    }

    /**
    * Returns the errors container
    *
    * @return Illuminate\Support\MessageBag
    */
    public function getMessageBag()
    {
        return $this->errors();
    }

    /**
    * Returns rules and data that needs validating
    *
    * @return array
    */
    public function getValidationInfo(array $rulesOverride = null, array $messagesOverride = null)
    {
        if ($this->exists) {
            $data = $this->getChanged();
            $rules = array_intersect_key($rulesOverride ?: static::$rules, $data);
        } else {
            $data = $this->attributes;
            $rules = $rulesOverride ?: static::$rules;
        }

        return count($rules) > 0 ?
            array($data, $rules, $messagesOverride ?: static::$messages) :
            array(null, null, null);
    }

    /**
    * Validate the Model
    *    runs the validator and binds any errors to the model
    *
    * @param array $rules
    * @param array $messages
    * @return bool
    */
    public function isValid(array $rulesOverride = null, array $messagesOverride = null)
    {
        $valid = true;
        list($data, $rules, $messages) = $this->getValidationInfo($rulesOverride);

        if ($rules) {
            $validator = Validator::make($data, $rules, $messages);
            $valid = $validator->passes();
        }

        if (!$valid) {
            $this->errorBag = $validator->errors();
        } elseif ($this->errorBag && $this->errorBag->any()) {
            $this->errorBag = new MessageBag();
        }

        return $valid;
    }

    /**
    * Called evertime a model is saved - to halt the save, return false
    *
    * @return bool
    */
    public function onSave()
    {
        return true;
    }

    /**
    * Called evertime a model is forceSaved - to halt the forceSave, return false
    *
    * @return bool
    */
    public function onForceSave()
    {
        return true;
    }

    /**
    * Save the model if it is valid
    *
    * @param array $rules
    * @param array $messages
    * @param closure $callback
    * @return bool
    */
    public function save(array $rules = array(), array $messages = array(), Closure $callback = null)
    {
        // evaluate onSave
        $before = is_null($callback) ? $this->onSave() : call_user_func($callback, $this);

        // check before & valid, then pass to parent
        return ($before && $this->isValid($rules, $messages)) ? parent::save() : false;
    }

    /**
    * Attempts to save model even if it doesn't validate
    *
    * @param array $rules
    * @param array $messages
    * @param callable $callback
    * @return bool
    */
    public function forceSave(array $rules = array(), array $messages = array(), Closure $callback = null)
    {
        // execute onForceSave
        $before = is_null($callback) ? $this->onForceSave() : call_user_func($callback, $this);

        // validate the model
        $this->isValid($rules, $messages);

        // save regardless of the result of validation
        return $before ? parent::save() : false;
    }
}
