<?php

namespace Drupal\du_menu_blocks\Plugin\Derivative;

use Drupal\system\Plugin\Derivative\SystemMenuBlock;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\system\Plugin\Block\SystemMenuBlock
 */
class DuMenuBlocks extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $category = 'DU Site Blocks';

    $this->derivatives['subnav'] = $base_plugin_definition;
    $this->derivatives['subnav']['admin_label'] = 'Subnav Block';
    $this->derivatives['subnav']['menu_name'] = 'main';
    $this->derivatives['subnav']['category'] = $category;
    // $this->derivatives['subnav']['config_dependencies']['config'] = [$entity->getConfigDependencyName()];
    $this->derivatives['parent_level'] = $base_plugin_definition;
    $this->derivatives['parent_level']['admin_label'] = 'Parent Level Nav';
    $this->derivatives['parent_level']['menu_name'] = 'main';
    $this->derivatives['parent_level']['category'] = $category;
    // $this->derivatives['parent_level']['config_dependencies']['config'] = [$entity->getConfigDependencyName()];
    return $this->derivatives;
  }

}
