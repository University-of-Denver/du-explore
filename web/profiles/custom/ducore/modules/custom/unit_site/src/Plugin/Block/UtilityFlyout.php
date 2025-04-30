<?php

namespace Drupal\unit_site\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides DU Menu blocks.
 *
 * @Block(
 *   id = "unit_utility_flyout",
 *   admin_label = @Translation("Utility Flyout"),
 *   category = @Translation("DU"),
 * )
 */
class UtilityFlyout extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $flyout_id = $config['flyout_id'];
    $flyout_content = $config['flyout_content'];

    $build = [
      '#theme' => 'utility_flyout',
      '#id' => $flyout_id,
      '#content' => check_markup($flyout_content['value'], $flyout_content['format']),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['flyout_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => $this->t('Used by utility nav links to identify a target flyout to open.'),
      '#default_value' => $config['flyout_id'],
    ];

    $form['flyout_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#format' => $config['flyout_content']['format'],
      '#default_value' => $config['flyout_content']['value'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['flyout_id'] = $values['flyout_id'];
    $this->configuration['flyout_content'] = $values['flyout_content'];
  }

}
