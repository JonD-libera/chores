document.addEventListener("DOMContentLoaded", function() {
    // Code to run after the DOM has fully loaded
    initializeButtons();
  });
  
  function initializeButtons() {
    // No initialization required here for this example
  }
  
  function buttonClicked(buttonLabel) {
    const mainContent = document.getElementById("lcars-main-content");
    console.error(mainContent);
    if (mainContent) {  // Check if mainContent is not null
      fetch(`fetch_data.php?buttonLabel=${encodeURIComponent(buttonLabel)}`)
        .then(response => response.json())
        .then(data => {
          const content = data.content || "No content available";
          mainContent.innerHTML = `<p>${content}</p>`;
        })
        .catch(error => {
          mainContent.innerHTML = `<p>Error fetching content: ${error}</p>`;
        });
    } else {
      console.error("mainContent element not found woops");
    }
  }
  