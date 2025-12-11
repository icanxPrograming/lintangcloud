<?php
// core/Controller.php

class Controller
{
  protected function view($viewPath, $data = [])
  {
    extract($data);
    require __DIR__ . '/../views/' . $viewPath . '.php';
  }

  protected function model($model)
  {
    require_once __DIR__ . '/../models/' . $model . '.php';
    return new $model;
  }
}
