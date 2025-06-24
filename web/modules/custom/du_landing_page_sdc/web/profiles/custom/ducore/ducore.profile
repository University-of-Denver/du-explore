<?php

/**
 * @file
 * Enables modules and site configuration for the DU Core profile.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function ducore_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // We'll add custom alterations to the site configuration form here.
}


/**
* Impliment to give user default role if they are on
* the system or campus support lists
*/
function ducore_user_presave(UserInterface $user) {
  // IMPRORTANT! Because of change to using CammelCase in usernames at DU wasn't 
  // retoactively applied, we are evaluating the match in lowercase.
  $support_eas = array('kevin.reynen','kent.hogue','charles.r.jones','joshua.mcgehee','alex.martinez','chris.hewitt','maximilian.fleischer','tj.sheu');
  $support_ur =  array('mac.whitney','nathan.boorom','staci.striegnitz','sherry.liang','anastasia.vylegzhanina','james.e.thomas','derek.vonschulz');
  $support_ba = array('rosi.hull');
  // @TODO - These arrays should be YML files or API endpoint that can be 
  // easily editted outside the PHP
  // Check to see if this user is on the list of campus or system support users
  if (in_array(strtolower($user->getAccountName()), $support_eas)) {
    $user->addRole('administrator');
  }
  if (in_array(strtolower($user->getAccountName()), $support_ba)) {
    $user->addRole('user_admin');
  }
  if (in_array(strtolower($user->getAccountName()), $support_ur)) {
    //check to see if the Pantheon environment is live
    if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
      if ($_ENV['PANTHEON_ENVIRONMENT'] != 'live') {
        $user->addRole('administrator');
      } else {
        $user->addRole('site_admin');
      }
    }
  }
  // @TODO - Remove user if no longer in original array 
}

