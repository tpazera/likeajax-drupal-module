<?php

namespace Drupal\likeajax\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'LikeAjax Block' Block
 * @Block(
 *  id = "likeajax",
 *  admin_label = @Translation("LikeAjax block"),
 *  category = @Translation("Like Ajax"),
 * )
 */
class LikeAjaxBlock extends BlockBase {

    /**
     * (@inheritdoc)
     */
    public function build() {
        $builtForm = \Drupal::formBuilder()->getForm('Drupal\likeajax\Form\LikeAjaxForm');
        $renderArray['form'] = $builtForm;

        return $renderArray;
    }
    
}