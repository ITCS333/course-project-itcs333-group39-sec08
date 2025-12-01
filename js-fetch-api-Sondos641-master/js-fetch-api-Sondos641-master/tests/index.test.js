/**
 * @jest-environment jsdom
 */

const fs = require('fs');
const path = require('path');

// Load the student's script and the HTML file
const html = fs.readFileSync(path.resolve(__dirname, '../index.html'), 'utf8');
const {
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
} = require('../index.js');

// Mock fetch API
global.fetch = jest.fn();

// Sample test data
const mockUsers = [
    {
        id: 1,
        name: "John Doe",
        username: "johndoe",
        email: "john@example.com",
        address: { city: "New York" }
    },
    {
        id: 2,
        name: "Jane Smith",
        username: "janesmith",
        email: "jane@example.com",
        address: { city: "Los Angeles" }
    },
    {
        id: 3,
        name: "Bob Johnson",
        username: "bob",
        email: "bob@example.com",
        address: { city: "New York" }
    }
];

// Reset the DOM before each test
beforeEach(() => {
    document.documentElement.innerHTML = html.toString();
    fetch.mockClear();
    setAllUsers([]); // Reset using our setter function
});

describe('Fetch API Tests', () => {
    test('should fetch users data from API', async () => {
        fetch.mockResolvedValueOnce({
            json: async () => mockUsers
        });

        const users = await fetchUsersData();
        
        expect(fetch).toHaveBeenCalledWith('https://jsonplaceholder.typicode.com/users');
        expect(users).toEqual(mockUsers);
        expect(getAllUsers()).toEqual(mockUsers); // Use our getter function
    });

    test('should handle fetch errors', async () => {
        fetch.mockRejectedValueOnce(new Error('API Error'));
        
        await expect(fetchUsersData()).rejects.toThrow('API Error');
    });
});

describe('Array Functions Tests', () => {
    test('should filter users by city name', () => {
        const filtered = filterUsersByCity(mockUsers, "New York");
        
        expect(filtered).toHaveLength(2);
        expect(filtered[0].name).toBe("John Doe");
        expect(filtered[1].name).toBe("Bob Johnson");
    });

    test('should filter users by city name (case insensitive)', () => {
        const filtered = filterUsersByCity(mockUsers, "new york");
        
        expect(filtered).toHaveLength(2);
    });

    test('should map users to summary format', () => {
        const summary = mapUserSummary(mockUsers);
        
        expect(summary).toHaveLength(3);
        expect(summary[0]).toEqual({ name: "John Doe", email: "john@example.com" });
        expect(summary[1]).toEqual({ name: "Jane Smith", email: "jane@example.com" });
    });

    test('should search users by name', () => {
        const results = searchUsersByName(mockUsers, "john");
        
        expect(results).toHaveLength(2); // "John Doe" and "Bob Johnson"
        expect(results.map(user => user.name)).toContain("John Doe");
        expect(results.map(user => user.name)).toContain("Bob Johnson");
    });

    test('should search users by name (case insensitive)', () => {
        const results = searchUsersByName(mockUsers, "JANE");
        
        expect(results).toHaveLength(1);
        expect(results[0].name).toBe("Jane Smith");
    });

    test('should calculate average username length', () => {
        const average = calculateAverageUsernameLength(mockUsers);
        const expectedAverage = (7 + 9 + 3) / 3; // "johndoe", "janesmith", "bob"
        
        expect(average).toBeCloseTo(expectedAverage, 2);
    });

    test('should return 0 for average username length with empty array', () => {
        const average = calculateAverageUsernameLength([]);
        
        expect(average).toBe(0);
    });
});

describe('DOM Manipulation Tests', () => {
    test('should display users in the container', () => {
        displayUsers(mockUsers);
        
        const container = document.getElementById('users-container');
        const userCards = container.querySelectorAll('.user-card');
        
        expect(userCards).toHaveLength(3);
        expect(userCards[0].textContent).toContain("John Doe");
        expect(userCards[0].textContent).toContain("john@example.com");
    });

    test('should update user count display', () => {
        updateUserCount(5);
        
        const countElement = document.getElementById('user-count');
        expect(countElement.textContent).toBe('Showing 5 users');
    });

    test('should show "No users to display" when count is 0', () => {
        updateUserCount(0);
        
        const countElement = document.getElementById('user-count');
        expect(countElement.textContent).toBe('No users to display');
    });
});

describe('Event Handling Tests', () => {
    test('should setup event listeners on buttons', () => {
        const loadBtn = document.getElementById('load-users-btn');
        const searchBtn = document.getElementById('search-btn');
        const filterBtn = document.getElementById('filter-city-btn');
        const clearBtn = document.getElementById('clear-btn');
        
        // Mock addEventListener
        loadBtn.addEventListener = jest.fn();
        searchBtn.addEventListener = jest.fn();
        filterBtn.addEventListener = jest.fn();
        clearBtn.addEventListener = jest.fn();
        
        setupEventListeners();
        
        expect(loadBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
        expect(searchBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
        expect(filterBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
        expect(clearBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
    });

    test('should handle search functionality', () => {
        setAllUsers(mockUsers); // Set test data using our setter
        const searchInput = document.getElementById('search-input');
        searchInput.value = 'John';
        
        handleSearch();
        
        const userCards = document.querySelectorAll('.user-card');
        expect(userCards).toHaveLength(2); // John Doe and Bob Johnson
    });

    test('should handle clear functionality', () => {
        // First add some users
        displayUsers(mockUsers);
        
        // Then clear
        handleClear();
        
        const container = document.getElementById('users-container');
        const countElement = document.getElementById('user-count');
        
        expect(container.innerHTML).toBe('');
        expect(countElement.textContent).toBe('No users to display');
    });
});
