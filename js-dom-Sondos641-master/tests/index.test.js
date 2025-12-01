/**
 * @jest-environment jsdom
 */

const fs = require('fs');
const path = require('path');

// Load the student's script and the HTML file
const html = fs.readFileSync(path.resolve(__dirname, '../index.html'), 'utf8');
const {
    changeHeadingText,
    changeBoxColor,
    addNewItem,
    highlightParagraph,
    removeElement
} = require('../index.js');

// Reset the DOM before each test
beforeEach(() => {
    document.documentElement.innerHTML = html.toString();
});

describe('DOM Manipulation Tests', () => {

    test('should change the main heading text', () => {
        changeHeadingText();
        const heading = document.getElementById('main-heading');
        expect(heading.textContent).toBe('DOM Manipulation Challenge');
    });

    test('should change the background color of the box', () => {
        changeBoxColor();
        const box = document.getElementById('box-to-modify');
        expect(box.style.backgroundColor).toBe('lightblue');
    });

    test('should add a new list item to the item list', () => {
        const initialList = document.getElementById('item-list');
        const initialLength = initialList.children.length;

        addNewItem();

        const updatedList = document.getElementById('item-list');
        expect(updatedList.children.length).toBe(initialLength + 1);
        expect(updatedList.lastElementChild.textContent).toBe('New Item');
    });

    test('should add the "highlight" class to the content paragraph', () => {
        const para = document.querySelector('.content-para');
        // Ensure it doesn't have the class initially
        expect(para.classList.contains('highlight')).toBe(false);

        highlightParagraph();

        expect(para.classList.contains('highlight')).toBe(true);
    });

    test('should remove the specified element from the DOM', () => {
        // Ensure the element exists before removal
        let elementToRemove = document.getElementById('to-be-removed');
        expect(elementToRemove).not.toBeNull();

        removeElement();

        // Check if the element has been removed
        elementToRemove = document.getElementById('to-be-removed');
        expect(elementToRemove).toBeNull();
    });

});

