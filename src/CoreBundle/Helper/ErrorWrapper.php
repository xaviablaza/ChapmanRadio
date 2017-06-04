<?php
/**
 * Created by PhpStorm.
 * User: michaelpollind
 * Date: 5/27/17
 * Time: 6:47 PM
 */

namespace CoreBundle\Helper;


use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ErrorWrapper
{
    private $errors = [];
    private $message;


    function __construct($message = null)
    {
        $this->message = $message;
    }

    public function setMessage($message)
    {
        $this->message = $message;

    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @param ConstraintViolationInterface  $error
     */
    public function addError(ConstraintViolationInterface $error)
    {
        $this->addKeyError($error->getPropertyPath(), $error->getMessage());
    }

    public function addKeyError($key,$error)
    {
        $this->errors[] = ["field" => $key, "message" => $error];
    }

    /**
     * @param array | ConstraintViolationListInterface $errors
     */
    public   function addErrors($errors)
    {
        if($errors instanceof ConstraintViolationList) {
            foreach ($errors as $error) {
                $this->addKeyError($error->getPropertyPath(), $error->getMessage());
            }
            return;
        }
        foreach ($errors as $key => $value ) {
            $this->addKeyError($key,$value);
        }
    }

}