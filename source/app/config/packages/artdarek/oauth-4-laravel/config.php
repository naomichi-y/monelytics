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
            'client_id'     => '477701589050466',
            'client_secret' => 'b2fcd169994977b2741f173667c92a6d',
            'scope'         => array('email'),
        ),

  )

);
