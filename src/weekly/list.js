const listSection = document.getElementById('week-list-section');

async function loadWeeks() {
  const res = await fetch('./api/api.php?resource=weeks');
  const json = await res.json();

  if (!json.success) {
    listSection.innerHTML = '<p>Failed to load weeks</p>';
    return;
  }

  listSection.innerHTML = '';

  json.data.forEach(w => {
    const article = document.createElement('article');
    article.innerHTML = `
      <h2>${w.title}</h2>
      <p><strong>Starts on:</strong> ${w.start_date}</p>
      <p>${w.description || ''}</p>
      <a href="details.html?id=${w.id}">View Details & Discussion</a>
    `;
    listSection.appendChild(article);
  });
}

loadWeeks();
