<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\CleanupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Acquires a lock.
 *
 * @Action(
 *   id = "eca_lock_acquire",
 *   label = @Translation("Lock: acquire"),
 *   description = @Translation("Acquires a lock.")
 * )
 */
class LockAcquire extends ConfigurableActionBase implements CleanupInterface {

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected LockBackendInterface $lock;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_base\Plugin\Action\LockAcquire $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setLock($container->get('lock'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'lock_name' => '',
      'lock_timeout' => '20',
      'lock_wait' => TRUE,
      'token_name' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['lock_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lock name'),
      '#description' => $this->t('The name identifies a lock, that may be shared by multiple processes. The lock will be automatically released when this ECA process is finished. This field supports tokens.'),
      '#default_value' => $this->configuration['lock_name'],
      '#required' => TRUE,
      '#weight' => -50,
    ];
    $form['lock_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Lock timeout in seconds'),
      '#description' => $this->t('This timeout is used as a maximum time threshold. When exceeded, the lock is automatically released for other processed again.'),
      '#default_value' => $this->configuration['lock_timeout'],
      '#required' => TRUE,
      '#weight' => -40,
    ];
    $form['lock_wait'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wait for lock until timeout exceeded'),
      '#description' => $this->t('When checked, this action automatically retries acquiring the lock, until the specified timeout above exceeded.'),
      '#default_value' => $this->configuration['lock_wait'],
      '#weight' => -30,
    ];
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#weight' => -20,
      '#description' => $this->t('Optionally define a token name to store the result of lock acquiry. The result value is <strong>1</strong> when acquiry was successful, and is set to <strong>0</strong> when it was not successful.'),
      '#eca_token_reference' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['lock_name'] = $form_state->getValue('lock_name');
    $this->configuration['lock_timeout'] = $form_state->getValue('lock_timeout');
    $this->configuration['lock_wait'] = !empty($form_state->getValue('lock_wait'));
    $this->configuration['token_name'] = $form_state->getValue('token_name');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $name = $this->getLockName();
    $timeout = $this->getTimeout();
    $lock_wait = $this->configuration['lock_wait'];
    $token_name = trim((string) $this->configuration['token_name']);
    $waiting_time = 0.0;
    while (!($acquired = $this->lock->acquire($name, $timeout)) && $lock_wait && ($waiting_time <= $timeout)) {
      $waiting_time += 0.25;
      usleep(250000);
    }
    if ($token_name !== '') {
      $this->tokenServices->addTokenData($token_name, $acquired ? 1 : 0);
    }
    if (!$acquired && $lock_wait && ($token_name === '')) {
      throw new \RuntimeException(sprintf("Wait exceeded timeout for lock name %s.", $name));
    }
  }

  /**
   * Set the lock service.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   */
  public function setLock(LockBackendInterface $lock): void {
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupAfterSuccessors(): void {
    $this->lock->release($this->getLockName());
  }

  /**
   * Get the configured lock name.
   *
   * @return string
   *   The lock name.
   */
  protected function getLockName(): string {
    $name = trim((string) $this->tokenServices->replaceClear($this->configuration['lock_name']));
    if ($name === '') {
      $name = 'lock_acquire';
    }
    // Always prefix with "eca:" to avoid conflicts with other components.
    return 'eca:' . $name;
  }

  /**
   * Get the configured timeout.
   *
   * @return float
   *   The timeout.
   */
  protected function getTimeout(): float {
    $timeout = trim((string) $this->tokenServices->replaceClear($this->configuration['lock_timeout']));
    if (empty($timeout)) {
      $timeout = 20.0;
    }
    return (float) $timeout;
  }

}
