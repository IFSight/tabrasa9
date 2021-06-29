<?php

namespace Drupal\fulcrum_whitelist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide whitelist authentication and redirects.
 */
class FulcrumWhitelistController extends ControllerBase {

  /**
   * Whitelist.
   *
   * @return string
   *   Return whitelist page.
   */
  public function whitelist($authtoken) {

    // Don't cache this page.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $config = \Drupal::config('fulcrum_whitelist.fulcrumwhitelistconfig');

    /** @var mixed $environmentAbbreviation */
    $environmentAbbreviation = $this->environmentAbbreviation();

    // Make sure we are configured.
    if (isset($environmentAbbreviation->abbr) &&
      isset($environmentAbbreviation->env) &&
      $environmentAbbreviation->abbr != 'unkn'
    ) {

      $database = \Drupal::database();

      // Lookup user by token, make sure there user and token is enabled.
      $query = $database->select('users_field_data', 'u');
      $query->leftJoin('fulcrum_whitelist_entity__field_user', 'w', 'u.uid = w.field_user_target_id');
      $query->leftJoin('fulcrum_whitelist_entity', 'e', 'w.entity_id = e.id');
      $query
        ->fields('u', ['uid', 'mail'])
        ->condition('u.status', '1')
        ->condition('e.status', '1')
        ->condition('e.name', "$authtoken")
        ->range(0, 1)
        ->addTag('fulcrum_whitelist');
      $count = $query->countQuery()->execute()->fetchField();

      if (!empty($count)) {

        $account = $query->execute()->fetchObject();
        if (isset($account->mail) && isset($account->uid)) {

          // Curl the whitelist with http://172.17.0.16:18888/
          // scvw/prd/1.2.3.4/foo@bar.com
          $params = [
            '@host' => $config->get('whitelist_host'),
            '@port' => $config->get('port'),
            '@abbr' => $environmentAbbreviation->abbr,
            '@env'  => $environmentAbbreviation->env,
          // \Drupal::request()->getClientIp(),
            '@ip'   => $_SERVER['HTTP_X_CLIENT_IP'],
            '@mail' => $account->mail,
          ];

          $url = $this->t('http://@host:@port/@abbr/@env/@ip/@mail', $params);

          // Add watchdog.
          \Drupal::logger('fulcrum_whitelist')->notice(
              $this->t(
                'Whitelist UID: @uid mail: @mail url: @url',
                [
                  '@url'  => $url,
                  '@uid'  => $account->uid,
                  '@mail' => $account->mail,
                ]
              ));

          $result = file_get_contents($url);

          // Return the themed wait redirect.
          return $this->dechromePage($config->get('wait_text'), TRUE);
        }
      }
      else {
        // Add watchdog.
        \Drupal::logger('fulcrum_whitelist')->notice(
            $this->t(
              'Whitelist attempt failed for token: @authtoken',
              ['@authtoken' => $authtoken]
            ));

        return $this->dechromePage($config->get('fail_text'));
      }
    }

    return $this->dechromePage($config->get('misconf_text'));
  }

  /**
   * Docs.
   *
   * @return array
   *   Return whitelist docs.
   */
  public function docs() {
    // don't cache this page
    // \Drupal::service('page_cache_kill_switch')->trigger();
    // Get the current user.
    $user = \Drupal::currentUser();
    $config = \Drupal::config('fulcrum_whitelist.fulcrumwhitelistconfig');

    if ($user->hasPermission('View unpublished Fulcrum Whitelist Entity entities')) {
      $docs_admin = $config->get('docs_admin');
    }

    return [
      'page' => [
        '#theme'  => 'docs',
        '#docs_intro'  => $config->get('docs_intro'),
        '#docs_user'   => $config->get('docs_user'),
        '#docs_admin'  => $docs_admin ?? NULL,
      ],
    ];

  }

  /**
   * Provide environment abbreviation.
   */
  private function environmentAbbreviation() {
    if (
      ($fconf = json_decode($_SERVER['FULCRUM_CONF'])) &&
      isset($fconf->env)
    ) {

      $config = \Drupal::config('fulcrum_whitelist.fulcrumwhitelistconfig');
      if (!empty($config)) {
        return (object) [
          'env'  => $fconf->env,
          'abbr' => $config->get('whitelist_abbr'),
        ];
      }
    }

    return FALSE;
  }

  /**
   * Provide render array for redirecting logins.
   */
  private function dechromePage($message, $inc_js = FALSE) {
    $js = '';

    if ($inc_js) {
      $config = \Drupal::config('fulcrum_whitelist.fulcrumwhitelistconfig');

      $js_build = [
        'page' => [
          '#theme' => 'javascript',
          '#delay' => $config->get('delay'),
          '#redirect' => $config->get('redirect'),
        ],
      ];

      $js = \Drupal::service('renderer')->renderRoot($js_build);
    }

    // Return the themed wait redirect.
    $build = [
      'page' => [
        '#theme'      => 'dechrome',
        '#message'    => $message,
        '#js'         => $js,
      ],
    ];

    $html = \Drupal::service('renderer')->renderRoot($build);
    $response = new Response();
    $response->setContent($html);

    return $response;
  }

}
