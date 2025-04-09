<?php

namespace Drupal\du_menu_blocks\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\Block\SystemMenuBlock;
use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;

/**
 * Provides DU Menu blocks.
 *
 * @Block(
 *   id = "du_menu_blocks",
 *   admin_label = @Translation("DU Menu blocks"),
 *   category = @Translation("Unused"),
 *   deriver = "Drupal\du_menu_blocks\Plugin\Derivative\DuMenuBlocks"
 *
 * )
 */
class DuMenuBlocks extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    unset($form['menu_levels']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the menu name. If we're on a node that is part of an inline unit,
    // then we need to use a different menu.
    $menu_name = 'main';
    if (\Drupal::moduleHandler()->moduleExists('du_inline_unit')) {
      $node = \Drupal::routeMatch()->getParameter('node');
      if (isset($node) && $node instanceof NodeInterface) {
        $inline_unit = du_inline_unit_get_inline_unit($node);
        if (!empty($inline_unit->field_menu->entity)) {
          $menu_name = $inline_unit->field_menu->entity->id();
        }
      }
    }

    $this->menu_tree = \Drupal::menuTree();

    // Build the typical default set of menu tree parameters.
    $this->parameters = $this->menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters = $this->parameters;
    $this->current_level = count($parameters->activeTrail) - 1;
    $this->parent_ids = array_values($parameters->activeTrail);

    // Setup our derivitives.
    switch ($this->getDerivativeId()) {
      case 'parent_level':
        // Get parent nav tree.
        $this->getParentNavTree($menu_name);
        break;

      case 'subnav':
        // Get subnav tree.
        $this->getSubNavTree($menu_name);
        break;
    }

    if (isset($this->tree) && count($this->tree)) {
      // Build the menublock.
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $this->tree = $this->menuTree->transform($this->tree, $manipulators);
      $build = $this->menuTree->build($this->tree);

      if (!empty($build['#theme'])) {
        // Add the configuration for use in menu_block_theme_suggestions_menu().
        $build['#du_menu_blocks_configuration'] = $this->configuration;
        // Remove the menu name-based suggestion so we can control its
        // precedence better in _block_theme_suggestions_menu().
        $build['#theme'] = 'menu';
      }

      // Find links that have children and add the has-subnav class to its
      // element.
      foreach ($this->tree as $element) {
        if (!empty($element->hasChildren)) {
          $id = $element->link->getPluginId();
          if (!empty($build['#items'][$id])) {
            // Find children of the current element.
            if (!empty($element->subtree)) {
              foreach ($element->subtree as $subelement) {
                if (!empty($subelement->hasChildren)) {
                  $subid = $subelement->link->getPluginId();
                  if (!empty($build['#items'][$id]['below'][$subid])) {
                    $build['#items'][$id]['below'][$subid]['attributes']->addClass('has-subnav');
                  }
                }
              }
            }
            else {
              // Only add the has-subnav class to this element if we're not
              // displaying its children directly below it.
              $build['#items'][$id]['attributes']->addClass('has-subnav');
            }
          }

          // When the first element is at level one, show the title as
          // "Overview", but only on the subnav. The parent nav is the back
          // arrow link so we want to keep the same title.
          if ($this->getDerivativeId() == 'subnav' && $element->depth == 1) {
            $build['#items'][$id]['title'] = 'Overview';
          }
        }
      }
    }

    // If we're showing a parent menu link with its children, check to see if
    // the first child links to the same page as the parent. If so, remove the
    // first child.
    if (isset($build['#items']) && count($build['#items']) == 1) {
      $parent = reset($build['#items']);
      if (!empty($parent['below']) && count($parent['below']) > 0) {
        $first_child = reset($parent['below']);
        if ($parent['url']->toUriString() == $first_child['url']->toUriString()) {
          $item_keys = array_keys($build['#items']);
          $parent_key = reset($item_keys);
          array_shift($build['#items'][$parent_key]['below']);
        }
      }
    }

    // Set cache and tag params so we can refresh cache on change in active
    // trail.
    $build['#cache']['contexts'][] = 'url.path';
    $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentNavTree($menu_name) {
    $parameters = $this->parameters;
    $root_item = NULL;
    if ($this->current_level > 2) {
      $parent_ids = array_reverse($this->parent_ids);
      $temp_parameters = clone $parameters;
      $temp_parameters->setRoot($this->parent_ids[0]);
      $child_tree = $this->menu_tree->load('temp', $temp_parameters);
      if (array_values($child_tree)[0]->hasChildren) {
        // Set parent item if it has children.
        $root_level = $this->current_level - 1;
      }
      else {
        // Set grandparent item if no children.
        $root_level = $this->current_level - 2;
      }
      $root_item = $parent_ids[$root_level];

      // Check to make sure root item is enabled.
      $uuid = preg_split('/:/', $root_item);
      $root_item_loaded = \Drupal::service('entity.repository')
        ->loadEntityByUuid('menu_link_content', $uuid);
      if (!$root_item_loaded->isEnabled()) {
        // Set great-grandparent item if it is disabled.
        $root_level = $root_level - 1;
      }
      $root_item = $parent_ids[$root_level];
    }
    elseif ($this->current_level == 2) {
      // Special case for 2nd level (because of weirdness in level 1/2 menu
      // design. First we need to load the current item's tree to see if it has
      // children.
      $temp_parameters = clone $parameters;
      $temp_parameters->setRoot($this->parent_ids[0]);
      $child_tree = $this->menu_tree->load('temp', $temp_parameters);

      if (array_values($child_tree)[0]->hasChildren) {
        $parent_ids = array_reverse($this->parent_ids);
        $root_item = $parent_ids[$this->current_level - 1];
      }
    }

    if (!empty($root_item)) {
      // Set the root to this item.
      $parameters->setRoot($root_item);
      $parameters->expandedParents = [];
      $parameters->setMaxDepth(1);
      $tree = $this->menu_tree->load($menu_name, $parameters);
      $this->tree = $tree;
      $this->configuration['suggestion'] = 'parent_level_block';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubNavTree($menu_name) {
    $parameters = $this->parameters;
    $current_level = $this->current_level;
    $parents = array_reverse($this->parent_ids);

    $current_item = $this->parent_ids[0];
    if (!empty($current_item)) {
      $parameters->setRoot($current_item);
      $parameters->expandedParents = [];
      $tree = $this->menu_tree->load($menu_name, $parameters);

      if ($tree[$current_item]->hasChildren) {
        // If current item has children set this item to the root and show
        // children.
        $parameters->setRoot($this->parent_ids[1]);
        $parameters->setMaxDepth(5);
        $parameters->addExpandedParents([$this->parent_ids[0]]);
        $parameters->addExpandedParents([$this->parent_ids[1]]);
        $parameters->excludeRoot();
        $current_tree[$current_item] = TRUE;
      }
      // Current item has no children.
      else {
        $parameters->setRoot($parents[1]);
        $current_tree[$this->parent_ids[1]] = TRUE;

        // Show parent item first, then this item with siblings below.
        if ($current_level > 2) {
          $parameters->setMinDepth($current_level - 2);
          $parameters->setMaxDepth($current_level - 1);
          $parameters->expandedParents = [];
        }
        else {
          $parameters->minDepth = NULL;
          $parameters->setMaxDepth(1);
        }
      }

      $tree = $this->menu_tree->load($menu_name, $parameters);
      $this->tree = array_intersect_key($tree, $current_tree);

      $this->configuration['suggestion'] = 'subnav';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route.menu_active_trails:' . 'main']);
  }

}
