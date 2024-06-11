<?php

namespace Drupal\hacks_forms;



class HacksApi {


  public function __construct() {

  }

  public function getScorecardFormElements($scorecardID, $putting = 1, $scores = null) {


    $score_node = \Drupal\node\Entity\Node::load($scorecardID);

    $course_node = $this->getCoursefromScorecardID($scorecardID);
    $course_handicap = $score_node->get('field_course_handicap')->value;
    $course_tee_color = $course_node->get('field_grint_tee_color')->value;
    $course_name = $course_node->get('field_grint_course')->value;


    $form['scores_info'] = [
      '#markup' =>
        '<span> Course: <span id="user_course_name">' . $this->removeIDFromCourseString($course_name) . '</span></span>' .
        '<span> Tee: <span id="user_course_tee_color">' . $course_tee_color . '</span></span>'.
        '<span> Handicap: <span id="user_course_handicap">' . $course_handicap . '</span></span>',

    ];
    $form['scores_table']['front'] = [
      '#type' => 'table',
      '#header' => ['Hole','1','2', '3','4','5','6','7','8','9', 'Out'],
      '#attributes' => ['class' => ['scorecard-table-input']], // Add your class here

    ];

    $pars = array_column($course_node->get('field_course_par_holes')->getValue(), 'value');
    $yards = array_column($course_node->get('field_hole_yards')->getValue(), 'value');
    $yards_out = $course_node->get('field_hole_yards_front')->value;
    $yards_in = $course_node->get('field_hole_yards_back')->value;
    $yards_total = $course_node->get('field_hole_yards_total')->value;
    $par_out = $course_node->get('field_course_par_front')->value;
    $par_in = $course_node->get('field_course_par_back')->value;
    $par_total = $course_node->get('field_course_par_total')->value;
    $hdcp = array_column($course_node->get('field_course_index_holes')->getValue(), 'value');




    $holeNumber = 0;
    // Iterate over each hole in the section
    $form['scores_table']['front']['yards'][0] = [
      '#markup' => 'Yards',
    ];
    $form['scores_table']['front']['hdcp'][0] = [
      '#markup' => 'Hdcp',
    ];
    $form['scores_table']['front']['par'][0] = [
      '#markup' => 'Par',
    ];
    $form['scores_table']['front']['score'][0] = [
      '#markup' => 'Score',
    ];
    if ($putting == 1) {
      $form['scores_table']['front']['putts'][0] = [
        '#markup' => 'Putts',
      ];
    }
    for ($i = 1; $i < 10; $i++) {
      $holeNumber++;

      $default_score = $score_node->get('field_18_hole_gross_score')->getValue() ?? '';
      $default_putts = $score_node->get('field_18_hole_putt_score')->getValue() ?? '';

      // Check if there is data in $scores for this hole, if not use the default
      $hole_score = isset($scores[$holeNumber]['score']) ? $scores[$holeNumber]['score'] : $default_score[$holeNumber - 1]['value'];
      $hole_putts = isset($scores[$holeNumber]['putts']) ? $scores[$holeNumber]['putts'] : $default_putts[$holeNumber - 1]['value'];


      $form['scores_table']['front']['yards'][$i] = [
        '#markup' => $yards[$holeNumber-1],
      ];
      $form['scores_table']['front']['hdcp'][$i] = [
        '#markup' => $hdcp[$holeNumber-1],
      ];
      $form['scores_table']['front']['par'][$i] = [
        '#markup' => $pars[$holeNumber-1],
      ];
      $form['scores_table']['front']['score'][$i] = [
        '#type' => 'textfield',
        '#size' => 1,
        '#required' => TRUE,  // Make the putts field required
        '#attributes' => [
          'pattern' => '[0-9]*',  // Ensures only digits can be entered
          'min' => '0',
        ],
        '#default_value' => $hole_score,

      ];
      if ($putting == 1) {
        $form['scores_table']['front']['putts'][$i] = [
          '#type' => 'textfield',
          '#size' => 1,
          '#required' => TRUE,  // Make the putts field required
          '#attributes' => [
            'pattern' => '[0-9]*',  // Ensures only digits can be entered
            'min' => '0',
          ],
          '#default_value' => $hole_putts,
        ];
      }
    }
    $form['scores_table']['front']['yards'][10] = [
      '#markup' => $yards_out,
    ];
    $form['scores_table']['front']['hdcp'][10] = [
      '#markup' => '',
    ];
    $form['scores_table']['front']['par'][10] = [
      '#markup' => $par_out,
    ];
    $form['scores_table']['front']['score'][10] = [
      '#markup' => '<span id="scores_table_front_score">0</span>',
    ];
    if ($putting == 1) {
      $form['scores_table']['front']['putts'][10] = [
        '#markup' => '<span id="scores_table_front_putts">0</span>',
      ];
    }


    $form['scores_table']['back'] = [
      '#type' => 'table',
      '#header' => ['Hole','10','11','12','13','14','15','16','17','18', 'In'],
      '#attributes' => ['class' => ['scorecard-table-input']], // Add your class here

    ];
    $form['scores_table']['back']['yards'][0] = [
      '#markup' => 'Yards',
    ];
    $form['scores_table']['back']['hdcp'][0] = [
      '#markup' => 'Hdcp',
    ];
    $form['scores_table']['back']['par'][0] = [
      '#markup' => 'Par',
    ];
    $form['scores_table']['back']['score'][0] = [
      '#markup' => 'Score',
    ];
    if ($putting == 1) {
      $form['scores_table']['back']['putts'][0] = [
        '#markup' => 'Putts',
      ];
    }
    for ($i = 1; $i < 10; $i++) {

      $form['scores_table']['back']['yards'][$i] = [
        '#markup' => $yards[$holeNumber-1],
      ];
      $form['scores_table']['back']['hdcp'][$i] = [
        '#markup' => $hdcp[$holeNumber-1],
      ];
      $holeNumber++;

      $default_score = $score_node->get('field_18_hole_gross_score')->getValue() ?? '';
      $default_putts = $score_node->get('field_18_hole_putt_score')->getValue() ?? '';

      // Check if there is data in $scores for this hole, if not use the default
      $hole_score = isset($scores[$holeNumber]['score']) ? $scores[$holeNumber]['score'] : $default_score[$holeNumber - 1]['value'];
      $hole_putts = isset($scores[$holeNumber]['putts']) ? $scores[$holeNumber]['putts'] : $default_putts[$holeNumber - 1]['value'];


      $form['scores_table']['back']['par'][$i] = [
        '#markup' => $pars[$holeNumber-1],
      ];
      $form['scores_table']['back']['score'][$i] = [
        '#type' => 'textfield',
        '#size' => 1,
        '#required' => TRUE,  // Make the putts field required
        '#attributes' => [
          'pattern' => '[0-9]*',  // Ensures only digits can be entered
          'min' => '0',
        ],
        '#default_value' => $hole_score,

      ];
      if ($putting == 1) {
        $form['scores_table']['back']['putts'][$i] = [
          '#type' => 'textfield',
          '#size' => 1,
          '#required' => TRUE,  // Make the putts field required
          '#attributes' => [
            'pattern' => '[0-9]*',  // Ensures only digits can be entered
            'min' => '0',
            ],
          '#default_value' => $hole_putts,
        ];
      }
    }
    $form['scores_table']['back']['yards'][10] = [
      '#markup' => $yards_in,
    ];
    $form['scores_table']['back']['hdcp'][10] = [
      '#markup' => '',
    ];
    $form['scores_table']['back']['par'][10] = [
      '#markup' => $par_in,
    ];
    $form['scores_table']['back']['score'][10] = [
      '#markup' => '<span id="scores_table_back_score">0</span>',
    ];
    if ($putting == 1) {
      $form['scores_table']['back']['putts'][10] = [
        '#markup' => '<span id="scores_table_back_putts">0</span>',
      ];
    }


    if ($putting = 1) {
      $form['scores_table']['total'] = [
        '#type' => 'table',
        '#header' => ['Gross Score', 'Net Score', 'Total Putts', 'Par', 'Yards'],
        '#attributes' => ['class' => ['scorecard-table-input']], // Add your class here

      ];
    } else {
      $form['scores_table']['total'] = [
        '#type' => 'table',
        '#header' => ['Gross Score', 'Net Score', 'Par', 'Yards'],
      ];
    }
    //$course_handicap
    $grossScore = 0;
    $totalPutts = 0;
    foreach ($scores as $hole => $data) {
      $grossScore += $data['score'];
      $totalPutts += $data['putts']; // Ensure 'putts' exists in your data
    }
    $netScore = $grossScore - $course_handicap;

// Calculate differences from par
    $grossDifference = $grossScore - $par_total;
    $netDifference = $netScore - $par_total;

// Format the differences to include a minus sign for under par
    $grossDifferenceFormatted = ($grossDifference > 0 ? "+" : ($grossDifference < 0 ? "" : "+")) . $grossDifference;
    $netDifferenceFormatted = ($netDifference > 0 ? "+" : ($netDifference < 0 ? "" : "+")) . $netDifference;

    $grossMarkup = "$grossScore ($grossDifferenceFormatted)";
    $netMarkup = "$netScore ($netDifferenceFormatted)";
    $puttsMarkup = "$totalPutts";

    $form['scores_table']['total']['score']['gross'] = [
      '#markup' => "<span id='scores_table_total_score'>$grossMarkup</span>",
    ];

    $form['scores_table']['total']['score']['net'] = [
      '#markup' => "<span id='scores_table_net_score'>$netMarkup</span>",
    ];

    $form['scores_table']['total']['score']['putts'] = [
      '#markup' => "<span id='scores_table_putt_score'>$puttsMarkup</span>",
    ];
    $form['scores_table']['total']['score']['par'] = [
      '#markup' => '<span id="scores_table_par_score">'.$par_total.'</span>',
    ];
    $form['scores_table']['total']['score']['yardage'] = [
      '#markup' => $yards_total,
    ];
    return $form;
  }

  public function getCoursefromScorecardID($scorecardID) {
    // Assuming $men_s_night_score_id is the ID of your 'men_s_night_score' node
    $men_s_night_score_id = $scorecardID;

    // Check if the node is loaded and is of the correct type
    // Query to find the 'men_s_night_round' node that references this 'men_s_night_score' node
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'men_s_night_round')
      ->condition('field_scores', $men_s_night_score_id)
      ->accessCheck(FALSE);
    $nids = $query->execute();

    // Load the 'men_s_night_round' node
    if (!empty($nids)) {
      $men_s_night_round_nid = reset($nids);
      $men_s_night_round_node = \Drupal\node\Entity\Node::load($men_s_night_round_nid);

      // Check if the 'men_s_night_round' node has a reference to the 'course' node
      if ($men_s_night_round_node && $men_s_night_round_node->hasField('field_course')) {
        $course_node_id = $men_s_night_round_node->get('field_course')->target_id;
        // Load the 'course' node
        $course_node = \Drupal\node\Entity\Node::load($course_node_id);
      }
    }
    return $course_node;
  }

  public function removeIDFromCourseString($courseString){
    // Regular expression to match the first occurrence of a text in parentheses
    $pattern = '/\([^\)]+\)/';
    // Replace the first occurrence with an empty string
    $result = preg_replace($pattern, '', $courseString, 1);
    // Trim the result to remove any leading or trailing spaces
    return trim($result);
  }


}
