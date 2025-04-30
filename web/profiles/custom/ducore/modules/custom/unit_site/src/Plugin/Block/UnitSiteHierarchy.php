<?php

namespace Drupal\unit_site\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Provides DU Menu blocks.
 *
 * @Block(
 *   id = "unit_site_hierarchy",
 *   admin_label = @Translation("Unit Site Heirarchy"),
 *   category = @Translation("DU"),
 * )
 */
class UnitSiteHierarchy extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::configFactory()->getEditable('du_unit_site_config.settings');
    $term_id = $config->get('unit_site_term');
    $ancestors = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($term_id);
    $ancestors = array_reverse($ancestors);

    $links = [];
    end($ancestors);
    $last_key = key($ancestors);
    foreach ($ancestors as $key => $term) {
      $link_values = $term->field_unit_url->getValue();
      if (!empty($link_values[0])) {
        if ($key !== $last_key) {
          $link_values[0]['options']['attributes']['class'][] = 'off-site';
        }

        $text = !empty($link_values[0]['title']) ? $link_values[0]['title'] : $link_values[0]['uri'];
        $url = Url::fromUri($link_values[0]['uri'], $link_values[0]['options']);
        $links[] = Link::fromTextAndUrl($text, $url)->toRenderable();
      }
    }

    if (\Drupal::moduleHandler()->moduleExists('du_inline_unit')) {
      $node = \Drupal::routeMatch()->getParameter('node');
      if (!empty($node) && $node instanceof NodeInterface) {
        // Add inline unit link if we're on a sub-inline unit page.
        $inline_unit = du_inline_unit_get_inline_unit($node);
        if (!empty($inline_unit)) {
          $parent = $inline_unit->field_parent_inline_unit->entity;
          if (!empty($parent)) {
            $text = $parent->title->value;
            $url = Url::fromUri('internal:' . $parent->field_home_page->entity->path->alias);
            $links[] = Link::fromTextAndUrl($text, $url)->toRenderable();
          }

          // Add current inline unit link.
          $text = $inline_unit->title->value;
          $url = Url::fromUri('internal:' . $inline_unit->field_home_page->entity->path->alias, [
            'attributes' => ['class' => 'current-site'],
          ]);
          $links[] = Link::fromTextAndUrl($text, $url)->toRenderable();
        }
      }
    }

    $build = [
      '#theme' => 'site_hierarchy',
      '#links' => $links,
      '#cache' => [
        'contexts' => ['url.path'],
      ],
    ];
    return $build;
  }

}
