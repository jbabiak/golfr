<?php

namespace Drupal\hacks_scoring;



use Drupal\node\Entity\Node;

class ScoringAPI
{


  public function __construct()
  {

  }

  public function calc_stroke_holes($scoreNode, $courseNode) {
    // Check if the course handicap is zero and return no adjustments
    $courseNode_handicap = (int) $scoreNode->get('field_course_handicap')->value;
    if ($courseNode_handicap == 0) {
      return array_fill(0, 18, 0); // No adjustment needed
    }

    // Retrieve the course index holes - the difficulty ranking for each hole
    $index_holes = $courseNode->get('field_course_index_holes')->getValue();
    $index_holes = array_column($index_holes, 'value');
    asort($index_holes); // Sort index holes by difficulty

    // Prepare the return array for stroke adjustments on each hole
    $strokes = array_fill(0, 18, 0);

    // Determine adjustment direction and sorting based on handicap
    if ($courseNode_handicap > 0) {
      $adjustment = -1; // Positive handicap - strokes removed from hardest holes
      $sorted_indices = array_keys($index_holes); // Hardest to easiest
    } else {
      $adjustment = 1; // Negative handicap - strokes added to easiest holes
      $sorted_indices = array_reverse(array_keys($index_holes)); // Easiest to hardest
    }

    // Apply the handicap, considering the course index holes
    for ($i = 0; $i < abs($courseNode_handicap); $i++) {
      $index = $i % 18; // Handle handicaps greater than 18
      $hole_number = $sorted_indices[$index];
      $strokes[$hole_number] += $adjustment;
    }

    return $strokes;
  }



  public function calculate_round_points($scoreNode, $courseNode) {
    $net_scores = $scoreNode->get('field_18_hole_net_score')->getValue();
    $gross_scores = $scoreNode->get('field_18_hole_gross_score')->getValue();
    $par_holes = $courseNode->get('field_course_par_holes')->getValue();
    $course_handicap = (int) $scoreNode->get('field_course_handicap')->value;

    $points = [];
    $counts = [
      'double_bogey_' => 0,
      'bogey' => 0,
      'par' => 0,
      'birdie' => 0,
      'eagle' => 0,
      'albatross' => 0,
    ];

    foreach ($net_scores as $index => $score) {
      $net = $score['value'];
      $gross = $gross_scores[$index]['value'];

      // Skip scoring if the gross score for the hole is 0
      if ($gross == 0) {
        $points[] = 0; // No points for this hole
        continue;
      }

      // Adjust net score if it ends in .5
      if (fmod($net, 1) == 0.5) {
        if ($course_handicap < 0) {
          $net += 0.5;
        } else if ($course_handicap > 0) {
          $net -= 0.5;
        }
      }

      $par = $par_holes[$index]['value'];
      $difference = $net - $par;

      switch (true) {
        case $difference <= -3:
          $points[] = 5;
          $counts['albatross']++;
          break;
        case $difference == -2:
          $points[] = 4;
          $counts['eagle']++;
          break;
        case $difference == -1:
          $points[] = 3;
          $counts['birdie']++;
          break;
        case $difference == 0:
          $points[] = 2;
          $counts['par']++;
          break;
        case $difference == 1:
          $points[] = 1;
          $counts['bogey']++;
          break;
        default: // 2 strokes or more over par
          $points[] = 0;
          $counts['double_bogey_']++;
          break;
      }
    }

    $total_points = array_sum($points);
    return ['points' => $points, 'counts' => $counts, 'total_points' => $total_points];
  }

  public function calculate_used_scores($scoreNode, $user) {
    // Find the 'men_s_night_round' that references this $scoreNode
    $round_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'men_s_night_round')
      ->condition('field_scores', $scoreNode->id())
      ->execute();

    $round_node = Node::load(reset($round_ids));

    // Find the 'men_s_night' node that uses this round
    $night_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'men_s_night')
      ->condition('field_weekly_rounds', $round_node->id())
      ->execute();

    $night_node = Node::load(reset($night_ids));
    $weeks_to_score = $night_node->get('field_weeks_to_score')->value;

    // Find all 'men_s_night_round' nodes from 'field_weekly_rounds'
    $all_round_ids = $night_node->get('field_weekly_rounds')->getValue();
    $all_round_ids = array_column($all_round_ids, 'target_id');

    // Find all scores for these rounds for the user
    $score_ids = [];
    foreach ($all_round_ids as $round_id) {
      $round_node = Node::load($round_id);
      $scores = $round_node->get('field_scores')->getValue();
      foreach ($scores as $score) {
        $score_node = Node::load($score['target_id']);
        if ($score_node
          && $score_node->get('field_player')->target_id == $user->id()
          && !empty($score_node->get('field_total_gross_score')->value)
          && $score_node->get('field_total_gross_score')->value > 0) {
          $score_ids[] = $score_node->id();
        }
      }
    }

    $all_scores = Node::loadMultiple($score_ids);
    usort($all_scores, function($a, $b) {
      return $b->get('field_league_points')->value <=> $a->get('field_league_points')->value;
    });

    // Mark the top x scores as used in total, based on weeks_to_score
    $used_in_total_value_for_current_node = null;

    // Mark the top x scores as used in total, based on weeks_to_score
    $updates = [];
    foreach ($all_scores as $index => $score) {
      $used_in_total = $index < $weeks_to_score;
      $current_used_in_total = $score->get('field_used_in_total')->value;
      if ($used_in_total !== $current_used_in_total) {
        $updates[$score->id()] = $used_in_total;
      }
    }

    return $updates; // Return the list of IDs and their new used_in_total values
  }

  public function calculate_round_skins($scoreNode) {
    // Find the 'men_s_night_round' node that references this $scoreNode
    $round_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'men_s_night_round')
      ->condition('field_scores', $scoreNode->id())
      ->execute();
    $round_node = \Drupal\node\Entity\Node::load(reset($round_ids));

    if (!$round_node) {
      return; // Exit if the round node was not found
    }

    // Retrieve all 'men_s_night_score' nodes referenced by the 'men_s_night_round'
    $score_ids = $round_node->get('field_scores')->getValue();
    $score_nodes = \Drupal\node\Entity\Node::loadMultiple(array_column($score_ids, 'target_id'));

    // Initialize an array to hold net scores for each hole across all score nodes
    $hole_comparisons = array_fill(0, 18, []);

    foreach ($score_nodes as $score_node) {
      $net_scores = $score_node->get('field_18_hole_net_score')->getValue();
      $gross_scores = $score_node->get('field_18_hole_gross_score')->getValue();

      foreach ($net_scores as $hole => $net_score) {
        // Skip the hole if the gross score is 0
        if ($gross_scores[$hole]['value'] == 0) {
          continue;
        }

        $hole_comparisons[$hole][] = [
          'score_node_id' => $score_node->id(),
          'net_score' => $net_score['value']
        ];
      }
    }

    // Determine the skins winner for each hole
    $skins_winners = array_fill(0, 18, null);
    foreach ($hole_comparisons as $hole => $scores) {
      usort($scores, function($a, $b) {
        return $a['net_score'] <=> $b['net_score'];
      });

      if (count($scores) > 1 && $scores[0]['net_score'] < $scores[1]['net_score']) {
        $skins_winners[$hole] = $scores[0]['score_node_id']; // Winner's node ID
      }
    }

    // Update the skins data for each score node
    foreach ($score_nodes as $score_node) {
      $skins_for_node = array_fill(0, 18, 0);

      foreach ($skins_winners as $hole => $winner_node_id) {
        if ($winner_node_id === $score_node->id()) {
          $skins_for_node[$hole] = 1;
        }
      }

      $score_node->set('field_18_hole_skins', $skins_for_node);

      // Sum and set the total skins
      $total_skins = array_sum($skins_for_node);
      $score_node->set('field_skins', $total_skins);

      $score_node->save();
    }
  }
}
