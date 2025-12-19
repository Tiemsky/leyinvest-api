<?php
return [
  //10 tentatives par minute
  'auth' => [
    'max' => 10,
    'window' => 1
  ],

  //3 tentatives toutes les 5 minutes
  'otp' => [
    'max' => 3,
    'window' => 5
  ],

  //Usage : routes API classiques,  60 requêtes par minute
  'api' => [
    'max' => 60,
    'window' => 1
  ],

  //1000 requêtes par minute par IP
  'global' => [
    'max' => 1000,
    'window' => 1
  ],

  //Usage : inscription utilisateur, 5 comptes par minute
  'register' => [
    'max' => 5,
    'window' => 1
  ],
];
