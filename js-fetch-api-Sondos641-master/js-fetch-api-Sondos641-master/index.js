// Global variable to store users data
let allUsers = [];

/**
 * Fetches user data from JSONPlaceholder API
 * @returns {Promise<Array>} Array of user objects
 */
async function fetchUsersData() {
  // TODO: Implement this function
  // 1. Use fetch to get data from 'https://jsonplaceholder.typicode.com/users'
  // 2. Convert response to JSON
  // 3. Store the result in allUsers global variable
  // 4. Return the users array
}

/**
 * Filters users by city name (case-insensitive)
 * @param {Array} users - Array of user objects
 * @param {string} cityName - Name of city to filter by
 * @returns {Array} Filtered array of users
 */
function filterUsersByCity(users, cityName) {
  // TODO: Implement this function
  // Use array.filter() to return users whose city matches cityName
  // Make comparison case-insensitive
}

/**
 * Maps users array to return only names and emails
 * @param {Array} users - Array of user objects
 * @returns {Array} Array of objects with name and email only
 */
function mapUserSummary(users) {
  // TODO: Implement this function
  // Use array.map() to return new array with objects containing only:
  // { name: user.name, email: user.email }
}

/**
 * Searches users by name (case-insensitive, partial match)
 * @param {Array} users - Array of user objects
 * @param {string} searchTerm - Search term
 * @returns {Array} Array of matching users
 */
function searchUsersByName(users, searchTerm) {
  // TODO: Implement this function
  // Use array.filter() to return users whose name includes searchTerm
  // Make search case-insensitive and allow partial matches
}

/**
 * Calculates average length of usernames
 * @param {Array} users - Array of user objects
 * @returns {number} Average username length
 */
function calculateAverageUsernameLength(users) {
  // TODO: Implement this function
  // Use array.reduce() to calculate the average length of all usernames
  // Return 0 if users array is empty
}

/**
 * Displays users in the DOM
 * @param {Array} users - Array of user objects to display
 */
function displayUsers(users) {
  // TODO: Implement this function
  // 1. Get the users-container element
  // 2. Clear its innerHTML
  // 3. For each user, create a div with class 'user-card'
  // 4. Set innerHTML to show user name, email, and city
  // 5. Append each user card to the container
  // 6. Update user count display
}

/**
 * Updates the user count display
 * @param {number} count - Number of users
 */
function updateUserCount(count) {
  // TODO: Implement this function
  // Update the text content of element with id 'user-count'
  // Display: "Showing X users" or "No users to display" if count is 0
}

/**
 * Sets up event listeners for all buttons and inputs
 */
function setupEventListeners() {
  // TODO: Implement this function
  // Add click event listeners to:
  // - load-users-btn: calls loadAndDisplayUsers()
  // - search-btn: calls handleSearch()
  // - filter-city-btn: calls handleCityFilter()
  // - clear-btn: calls handleClear()
  // Add input event listener to:
  // - search-input: calls handleSearch() with a debounce
}

/**
 * Handles loading and displaying users
 */
async function loadAndDisplayUsers() {
  // TODO: Implement this function
  // 1. Show loading message in users container
  // 2. Try to fetch users data
  // 3. Display the users
  // 4. Handle any errors by showing error message
}

/**
 * Handles search functionality
 */
function handleSearch() {
  // TODO: Implement this function
  // 1. Get search term from search-input
  // 2. If search term is empty, display all users
  // 3. Otherwise, search users by name and display results
}

/**
 * Handles city filter (filters by "New York" equivalent city)
 */
function handleCityFilter() {
  // TODO: Implement this function
  // Filter allUsers by city name and display results
  // Note: JSONPlaceholder users don't have "New York", 
  // so filter by any city that exists in the data (e.g., "Gwenborough")
}

/**
 * Handles clearing all displayed users
 */
function handleClear() {
  // TODO: Implement this function
  // Clear the users container and reset user count
}

// Initialize event listeners when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
});

/**
 * Gets the current users array (for testing)
 * @returns {Array} Current users array
 */
function getAllUsers() {
  return allUsers;
}

/**
 * Sets the users array (for testing)
 * @param {Array} users - Users array to set
 */
function setAllUsers(users) {
  allUsers = users;
}

// Do not edit the lines below.
// These lines are for testing purposes.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        fetchUsersData,
        filterUsersByCity,
        mapUserSummary,
        searchUsersByName,
        calculateAverageUsernameLength,
        displayUsers,
        updateUserCount,
        setupEventListeners,
        loadAndDisplayUsers,
        handleSearch,
        handleCityFilter,
        handleClear,
	getAllUsers,
	setAllUsers
    };
}
