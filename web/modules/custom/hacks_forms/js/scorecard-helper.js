(function (Drupal) {
  Drupal.behaviors.modalScorecard = {
    attach: function (context, settings) {
      function calculateSum(selector) {
        let sum = 0;
        context.querySelectorAll(selector).forEach(input => {
          sum += parseInt(input.value, 10) || 0;
        });
        return sum;
      }

      function updateDisplay(sum, resultId) {
        const scoreSpan = document.getElementById(resultId);
        if (scoreSpan) {
          scoreSpan.textContent = sum;
        }
      }

      function updateScores() {
        const frontScore = calculateSum('input[data-drupal-selector^="edit-front-score-"]');
        const backScore = calculateSum('input[data-drupal-selector^="edit-back-score-"]');
        const totalGrossScore = frontScore + backScore;

        updateDisplay(frontScore, 'scores_table_front_score');
        updateDisplay(backScore, 'scores_table_back_score');

        const parScoreElement = document.getElementById('scores_table_par_score');
        const parScore = parScoreElement ? parseInt(parScoreElement.textContent, 10) : 0;
        const difference = totalGrossScore - parScore;
        const sign = difference > 0 ? '+' : '';

        updateDisplay(`${totalGrossScore} (${sign}${difference})`, 'scores_table_total_score');

        const handicapElement = document.getElementById('user_course_handicap');
        const courseHandicap = handicapElement ? parseInt(handicapElement.textContent, 10) : 0;
        const netScore = totalGrossScore - courseHandicap;
        updateDisplay(`${netScore} (${sign}${difference - courseHandicap})`, 'scores_table_net_score');
      }

      function updatePutts() {
        const frontPutts = calculateSum('input[data-drupal-selector^="edit-front-putts-"]');
        const backPutts = calculateSum('input[data-drupal-selector^="edit-back-putts-"]');
        const totalPutts = frontPutts + backPutts;

        updateDisplay(frontPutts, 'scores_table_front_putts');
        updateDisplay(backPutts, 'scores_table_back_putts');
        updateDisplay(totalPutts, 'scores_table_putt_score');
      }

      function updateAll() {
        updateScores();
        updatePutts();
      }

      // Initial update and setup event listeners for all related input fields
      updateAll();
      context.querySelectorAll('input[data-drupal-selector^="edit-front-score-"], input[data-drupal-selector^="edit-back-score-"], input[data-drupal-selector^="edit-front-putts-"], input[data-drupal-selector^="edit-back-putts-"]').forEach(element => {
        element.addEventListener('input', updateAll);
      });
    }
  };
})(Drupal);
