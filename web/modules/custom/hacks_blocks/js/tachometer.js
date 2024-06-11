(function ($, Drupal) {
  Drupal.behaviors.hacksBlocksTachometer = {
    attach: function (context, settings) {
      initTachometer();
    }
  };
})(jQuery, Drupal);
function initTachometer() {
  const tachometerElement = document.getElementById('tachometer');

  if (tachometerElement) {
    const percentage = parseFloat(tachometerElement.dataset.percentage || '0');
    const rotation = calculateRotation(percentage);
    const needle = tachometerElement.querySelector('.line');
    if (needle) {
      needle.style.transform = `rotate(${rotation}deg)`;
    }
  } else {
    console.error('Tachometer element not found');
  }

}

function calculateRotation(percentage) {
  const points = [
    { percent: 0,   rotation: 238, emoji: '😇' },
    { percent: 20,  rotation: 269, emoji: '🤔' },
    { percent: 40,  rotation: 300, emoji: '🤨' },
    { percent: 50,  rotation: 330, emoji: '😒' },
    { percent: 60,  rotation: 360, emoji: '🙄' },
    { percent: 70,  rotation: 390, emoji: '🤥' },
    { percent: 80,  rotation: 420, emoji: '😠' },
    { percent: 90,  rotation: 450, emoji: '💩' },
    { percent: 100, rotation: 480, emoji: '💩' },
    { percent: 110, rotation: 511, emoji: '💩' }
  ];

  // Find the two points the percentage falls between
  let lowerBound = points[0];
  let upperBound = points[points.length - 1];
  for (let i = 0; i < points.length - 1; i++) {
    if (percentage >= points[i].percent && percentage <= points[i + 1].percent) {
      lowerBound = points[i];
      upperBound = points[i + 1];
      break;
    }
  }

  // Interpolate between the two points to find the exact rotation
  const rangePercent = (percentage - lowerBound.percent) / (upperBound.percent - lowerBound.percent);
  const rotation = lowerBound.rotation + rangePercent * (upperBound.rotation - lowerBound.rotation);

  const emojiElement = document.querySelector('.emoji');
  if (emojiElement) {
    emojiElement.textContent = lowerBound.emoji;
  }

  return rotation;
}
