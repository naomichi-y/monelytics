<?php

return array(

  /*
  |--------------------------------------------------------------------------
  | oAuth Config
  |--------------------------------------------------------------------------
  */

  /**
   * Storage
   */
  'storage' => 'Session',

  /**
   * Consumers
   */
  'consumers' => array(

    /**
     * Facebook
     */
        'Facebook' => array(
            'client_id'     => '477701712383787',
            'client_secret' => '43773fcecff8d82755503f395760c703',
            'scope'         => array('email'),
        ),

  )

);
