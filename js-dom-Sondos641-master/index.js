/**
 * Selects the element with the id 'main-heading' and changes its text content to 'DOM Manipulation Challenge'.
 */

function changeHeadingText() {
  // TODO: Implement this function
  let text = document.getElementById('main-heading');
  console.log(text.textContent);
  text.textContent = "DOM Manipulation Challenge";
}

/**
 * Selects the element with the id 'box-to-modify' and changes its background color to 'lightblue'.
 */
function changeBoxColor() {
  // TODO: Implement this function
  let colour = document.getElementById('box-to-modify');
  colour.style.backgroundColor = 'lightblue';
}

/**
 * Creates a new <li> element, sets its text content to 'New Item', and appends it to the <ul> with the id 'item-list'.
 */
function addNewItem() {
  // TODO: Implement this function
  let ul = document.getElementById('item-list');
  let newLi = document.createElement('li');
  newLi.textContent = 'New Item';
  ul.appendChild(newLi);
}

/**
 * Selects the paragraph with the class 'content-para' and adds the class 'highlight' to it.
 */
function highlightParagraph() {
  // TODO: Implement this function
  let r = document.querySelector('.content-para');
  r.classList.add('highlight');
}

/**
 * Selects the element with the id 'to-be-removed' and removes it from the DOM.
 */
function removeElement() {
  // TODO: Implement this function
  let el = document.getElementById('to-be-removed');
  el.remove();
}


// Do not edit the lines below.
// These lines are for testing purposes.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        changeHeadingText,
        changeBoxColor,
        addNewItem,
        highlightParagraph,
        removeElement
    };
}

