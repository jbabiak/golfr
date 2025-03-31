<?php

namespace Drupal\matchplay_bracket\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides a Matchplay Bracket Block.
 *
 * @Block(
 *   id = "matchplay_bracket_block",
 *   admin_label = @Translation("Matchplay Bracket Block")
 * )
 */
class MatchplayBracketBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $routeMatch;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  public function build() {
    $node = $this->routeMatch->getParameter('node');

    if (!$node instanceof Node || $node->bundle() !== 'matchplay_season') {
      return ['#markup' => 'No bracket available.'];
    }

    $matches = $node->get('field_matches')->referencedEntities();

    $results_by_round = [];
    $names_by_round = [];
    $loser_results = [];
    $loser_names_by_round = [];
    $match_links = [];

    foreach ($matches as $match) {
      $round_number = (int) $match->get('field_round_number')->value ?: 1;
      $is_loser = $round_number >= 100 && $round_number < 200;
      $index = $is_loser ? $round_number - 100 : $round_number - 1;

      $team1 = $match->get('field_team_1_players')->referencedEntities()[0] ?? null;
      $team2 = $match->get('field_team_2_players')->referencedEntities()[0] ?? null;
      $winner = $match->get('field_winning_players')->referencedEntities()[0] ?? null;

      $team1_name = $team1 ? $this->getUserName($team1) : 'TBD';
      $team2_name = $team2 ? $this->getUserName($team2) : 'TBD';

      $score = [0, 0];
      if ($winner) {
        if ($team1 && $winner->id() === $team1->id()) {
          $score = [1, 0];
        } elseif ($team2 && $winner->id() === $team2->id()) {
          $score = [0, 1];
        }
      }

      $entry = [
        'team1' => $team1_name,
        'team2' => $team2_name,
        'nid' => $match->id(),
      ];

      if ($is_loser) {
        if (!isset($loser_results[$index])) {
          $loser_results[$index] = [];
          $loser_names_by_round[$index] = [];
        }
        $loser_results[$index][] = $score;
        $loser_names_by_round[$index][] = $entry;
      } else {
        if (!isset($results_by_round[$index])) {
          $results_by_round[$index] = [];
          $names_by_round[$index] = [];
        }
        $results_by_round[$index][] = $score;
        $names_by_round[$index][] = $entry;
      }

      // Winning match line links
      if (!$match->get('field_winning_match')->isEmpty()) {
        $target = $match->get('field_winning_match')->referencedEntities()[0] ?? null;
        if ($target) {
          $match_links[] = [
            'from' => $match->id(),
            'to' => $target->id(),
          ];
        }
      }
    }

    ksort($results_by_round);
    ksort($names_by_round);
    ksort($loser_results);
    ksort($loser_names_by_round);

    $bracket_data = [
      'results' => array_values($results_by_round),
      'team_names_by_round' => array_values($names_by_round),
      'loser_results' => array_values($loser_results),
      'loser_team_names_by_round' => array_values($loser_names_by_round),
      'match_links' => $match_links,
    ];

    return [
      '#markup' => '<div id="matchplay-bracket"></div>',
      '#attached' => [
        'library' => ['matchplay_bracket/custom_bracket'],
        'drupalSettings' => [
          'matchplayBracket' => $bracket_data,
        ],
      ],
    ];
  }

  protected function getUserName(User $user) {
    return $user->get('field_name')->value ?? $user->getDisplayName();
  }
}
