(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.customBracket = {
    attach: function (context, settings) {
      const data = drupalSettings.matchplayBracket;
      const results = data.results || [];
      const teamNames = data.team_names_by_round || [];
      const loserResults = data.loser_results || [];
      const loserTeamNames = data.loser_team_names_by_round || [];
      const matchLinks = data.match_links || [];
      const matchMap = [];

      once('customBracket', '#matchplay-bracket', context).forEach((el) => {
        const $bracket = $(el).addClass('custom-bracket');
        $bracket.empty();

        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.classList.add('bracket-lines');

        const createBracket = (rounds, teamNames, bracketId, label, roundOffset = 0) => {
          const $container = $('<div class="bracket-section"></div>').attr('id', bracketId);
          $container.append(`<h3>${label}</h3>`);

          let currentGlobalRow = 0;

          rounds.forEach((matches, roundIndex) => {
            const adjustedRoundIndex = roundIndex + roundOffset;
            const $round = $('<div class="bracket-round"></div>').attr('data-round', adjustedRoundIndex).appendTo($container);

            if (!matchMap[adjustedRoundIndex]) matchMap[adjustedRoundIndex] = [];

            // Restore spacing logic (vertical blanks before first match in a round)
            let baseBlank = adjustedRoundIndex >= 3 ? adjustedRoundIndex : (adjustedRoundIndex === 2 ? 1 : 0);
            for (let i = 0; i < baseBlank; i++) {
              $round.append('<div class="bracket-match blank"></div>');
              currentGlobalRow++;
            }

            matches.forEach((match, matchIndex) => {
              const [score1, score2] = match;
              const meta = teamNames[roundIndex]?.[matchIndex] || {};
              const name1 = meta.team1 || 'TBD';
              const name2 = meta.team2 || 'TBD';
              const matchNid = meta.nid || null;

              const $match = $(`
                <div class="bracket-match"
                     data-round="${adjustedRoundIndex}"
                     data-index="${matchIndex}"
                     data-row="${currentGlobalRow}"
                     ${matchNid ? `data-nid="${matchNid}"` : ''}>
                  <div class="team">${name1} <span class="score">${score1}</span></div>
                  <div class="team">${name2} <span class="score">${score2}</span></div>
                </div>
              `);

              $round.append($match);
              matchMap[adjustedRoundIndex].push($match[0]);
              currentGlobalRow++;

              // Spacer logic for double-matches in a round
              const next = matches[matchIndex + 1];
              if (
                roundIndex > 0 &&
                next &&
                teamNames[roundIndex]?.[matchIndex + 1]
              ) {
                const next1 = teamNames[roundIndex][matchIndex + 1].team1 || 'TBD';
                const next2 = teamNames[roundIndex][matchIndex + 1].team2 || 'TBD';
                const nextIsBye = (next1 === 'TBD' && next2 !== 'TBD') || (next2 === 'TBD' && next1 !== 'TBD');

                const isByeMatch = (name1 === 'TBD' && name2 !== 'TBD') || (name2 === 'TBD' && name1 !== 'TBD');

                if (!isByeMatch && !nextIsBye) {
                  $round.append('<div class="bracket-match blank"></div>');
                  $round.append('<div class="bracket-match blank"></div>');
                  currentGlobalRow += 2;
                } else if (isByeMatch && !nextIsBye) {
                  $round.append('<div class="bracket-match blank"></div>');
                  currentGlobalRow += 1;
                }
              }
            });
          });

          return $container;
        };

        const $winnerBracket = createBracket(results, teamNames, 'main-bracket', 'Winner Bracket', 0);
        const $loserBracket = createBracket(loserResults, loserTeamNames, 'loser-bracket', 'Loser Bracket', 0);

        $bracket.append($winnerBracket, $loserBracket, svg);

        setTimeout(() => drawLines($bracket[0], svg), 100);
      });

      function drawLines(container, svg) {
        svg.innerHTML = '';
        const containerRect = container.getBoundingClientRect();

        const width = container.scrollWidth || container.offsetWidth || 1200;
        const height = container.scrollHeight || container.offsetHeight || 800;
        svg.setAttribute("width", width);
        svg.setAttribute("height", height);

        matchLinks.forEach(link => {
          const fromEl = container.querySelector(`[data-nid="${link.from}"]`);
          const toEl = container.querySelector(`[data-nid="${link.to}"]`);

          if (fromEl && toEl) {
            const start = getCenter(fromEl, containerRect);
            const end = getCenter(toEl, containerRect);
            drawPath(svg, start, end);
          }
        });
      }

      function drawPath(svg, start, target) {
        const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
        path.setAttribute('d', `
          M${start.x},${start.y}
          H${target.x - 140}
          V${target.y}
          H${target.x}
        `);
        path.setAttribute('stroke', '#aaa');
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke-width', '2');
        svg.appendChild(path);
      }

      function getCenter(element, containerRect) {
        const rect = element.getBoundingClientRect();
        return {
          x: rect.left - containerRect.left + rect.width,
          y: rect.top - containerRect.top + rect.height / 2,
        };
      }
    }
  };
})(jQuery, Drupal, drupalSettings, once);
