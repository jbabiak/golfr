(function (Drupal, once) {
  Drupal.behaviors.gcUploadScorecard = {
    attach(context) {
      once('gc-upload-scorecard', '.gc-upload-scorecard', context).forEach((wrap) => {
        const courseInput = wrap.querySelector('input[data-gc-upload-course]');
        const teeInput = wrap.querySelector('input[data-gc-upload-tee]');

        const facilityHidden = wrap.querySelector('input[name="gc_facility_id"]');
        const courseIdHidden = wrap.querySelector('input[name="gc_course_id"]');

        function parseCourseValue(val) {
          // Expect: [facilityId=123|courseId=456] Name...
          const m = (val || '').match(/\[facilityId=(\d+)\|courseId=(\d+)\]/);
          if (!m) return null;
          return { facilityId: m[1], courseId: m[2] };
        }

        if (courseInput && facilityHidden && courseIdHidden) {
          courseInput.addEventListener('change', () => {
            const parsed = parseCourseValue(courseInput.value);
            if (parsed) {
              facilityHidden.value = parsed.facilityId;
              courseIdHidden.value = parsed.courseId;
            } else {
              facilityHidden.value = '';
              courseIdHidden.value = '';
            }
          });
        }

        // Core autocomplete uses data-autocomplete-path; we can append params before focus.
        if (teeInput && facilityHidden && courseIdHidden) {
          const basePath = teeInput.getAttribute('data-autocomplete-path');
          teeInput.addEventListener('focus', () => {
            const fid = (facilityHidden.value || '').trim();
            const cid = (courseIdHidden.value || '').trim();
            if (fid && cid) {
              teeInput.setAttribute(
                'data-autocomplete-path',
                basePath + '?facility_id=' + encodeURIComponent(fid) + '&course_id=' + encodeURIComponent(cid)
              );
            }
          });
        }
      });

      // Fix club autocomplete -> ensure AJAX triggers AFTER selection value is inserted.
      once('gc-upload-club-autocomplete-fix', '#gc-upload-post-fields-wrapper input[name="club"]', context).forEach((clubInput) => {
        const triggerChangeSoon = () => {
          // Wait a tick so jQuery UI finishes updating the input value.
          window.setTimeout(() => {
            clubInput.dispatchEvent(new Event('change', { bubbles: true }));
          }, 0);
        };

        if (window.jQuery) {
          const $club = window.jQuery(clubInput);

          // Runs on actual selection.
          $club.on('autocompleteselect.gc_upload', () => {
            triggerChangeSoon();
          });

          // Some interactions only reliably hit close.
          $club.on('autocompleteclose.gc_upload', () => {
            // If it looks like a selected value, trigger.
            const v = (clubInput.value || '').trim();
            if (v.match(/^\(\s*\d+\s*\)/) || v.match(/\(\s*\d+\s*\)\s*$/)) {
              triggerChangeSoon();
            }
          });
        }
      });
    }
  };
})(Drupal, once);
