<?php

namespace Drupal\fulcrum_whitelist\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Fulcrum Whitelist Entity edit forms.
 *
 * @ingroup fulcrum_whitelist
 */
class FulcrumWhitelistEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\fulcrum_whitelist\Entity\FulcrumWhitelistEntity $entity */
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Fulcrum Whitelist Entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Fulcrum Whitelist Entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.fulcrum_whitelist_entity.canonical', ['fulcrum_whitelist_entity' => $entity->id()]);
    return $status;
  }

}
