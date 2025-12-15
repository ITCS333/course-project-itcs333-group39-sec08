const listSection = document.getElementById('week-list-section');

function createWeekArticle(weekData) {
  const article = document.createElement('article');
  article.innerHTML = `
    <h2>${weekData.title}</h2>
    <p><strong>Starts on:</strong> ${weekData.start_date}</p>
    <p>${weekData.description || ''}</p>
    <a href="details.html?id=${weekData.id}">View Details & Discussion</a>
  `;
  return article;
}

async function loadWeeks() {
  const res = await fetch('./api/api.php?resource=weeks');
  const json = await res.json();

  if (!json.success) {
    listSection.innerHTML = '<p>Failed to load weeks</p>';
    return;
  }

  listSection.innerHTML = '';

  json.data.forEach(w => {
    const article = createWeekArticle(w);
    listSection.appendChild(article);
  });
}

loadWeeks();

// Make function available for testing
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { createWeekArticle, loadWeeks };
} else if (typeof window !== 'undefined') {
  window.createWeekArticle = createWeekArticle;
  window.loadWeeks = loadWeeks;
}
