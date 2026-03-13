// Personal Budget & Finance Manager - JavaScript

// Global variables
let currentMonth = '';
let expenseChart = null;
let monthlyReportChart = null;
let currentUser = window.userData || null; // Get user data from PHP

// ============ LOGOUT FUNCTION ============

// Handle logout
async function handleLogout() {
    if (!confirm('Are you sure you want to logout?')) {
        return;
    }
    
    try {
        const response = await fetch('auth.php?action=logout', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Logged out successfully!');
            setTimeout(() => {
                window.location.href = 'auth.html';
            }, 1000);
        }
    } catch (error) {
        console.error('Logout failed:', error);
        window.location.href = 'auth.html';
    }
}

// Initialize app on page load
document.addEventListener('DOMContentLoaded', function() {
    // Authentication is now handled server-side by dashboard.php
    // User data is already available in window.userData
    
    // Set current month
    const now = new Date();
    currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    document.getElementById('current-month').value = currentMonth;
    
    // Set today's date for expense form
    document.getElementById('expense-date').valueAsDate = now;
    
    // Event listeners
    document.getElementById('current-month').addEventListener('change', handleMonthChange);
    document.getElementById('income-form').addEventListener('submit', handleIncomeSubmit);
    document.getElementById('expense-form').addEventListener('submit', handleExpenseSubmit);
    document.getElementById('budget-form').addEventListener('submit', handleBudgetSubmit);
    document.getElementById('goal-form').addEventListener('submit', handleGoalSubmit);
    
    // Income calculation listener
    const incomeInputs = ['basic-salary', 'allowances', 'bonuses', 'other-income'];
    incomeInputs.forEach(id => {
        document.getElementById(id).addEventListener('input', calculateTotalIncome);
    });
    
    // Load initial data
    loadDashboard();
    loadIncome();
    loadExpenses();
    loadBudget();
    loadSavingsGoals();
    loadReports();
});

// ============ NAVIGATION ============

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Activate clicked tab
    event.target.classList.add('active');
    
    // Load section data
    if (sectionId === 'dashboard') loadDashboard();
    else if (sectionId === 'reports') loadReports();
}

function handleMonthChange() {
    currentMonth = document.getElementById('current-month').value;
    loadDashboard();
    loadIncome();
    loadExpenses();
    loadBudget();
    loadReports();
}

// ============ UTILITY FUNCTIONS ============

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast show' + (isError ? ' error' : '');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function formatCurrency(amount) {
    return `NPR ${parseFloat(amount).toLocaleString('en-NP', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

async function apiCall(action, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin' // IMPORTANT: Include cookies in the request!
        };
        
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(`server.php?action=${action}`, options);
        const result = await response.json();
        
        // Check if session expired
        if (result.redirect === 'auth.html') {
            showToast('Session expired. Please login again.', true);
            setTimeout(() => {
                window.location.href = 'auth.html';
            }, 2000);
            return result;
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Connection error' };
    }
}

// ============ INCOME FUNCTIONS ============

function calculateTotalIncome() {
    const basicSalary = parseFloat(document.getElementById('basic-salary').value) || 0;
    const allowances = parseFloat(document.getElementById('allowances').value) || 0;
    const bonuses = parseFloat(document.getElementById('bonuses').value) || 0;
    const otherIncome = parseFloat(document.getElementById('other-income').value) || 0;
    
    const total = basicSalary + allowances + bonuses + otherIncome;
    document.getElementById('total-income-display').textContent = formatCurrency(total);
    
    // Calculate tax
    if (total > 0) {
        calculateTaxDisplay(total * 12);
    }
}

async function calculateTaxDisplay(annualIncome) {
    const result = await apiCall(`calculate_tax&income=${annualIncome}`);
    
    if (result.success) {
        document.getElementById('annual-income').textContent = formatCurrency(result.data.annual_income);
        document.getElementById('income-tax').textContent = formatCurrency(result.data.income_tax);
        document.getElementById('ssf-contribution').textContent = formatCurrency(result.data.ssf_contribution);
        document.getElementById('total-tax').textContent = formatCurrency(result.data.total_tax);
        document.getElementById('net-annual').textContent = formatCurrency(result.data.net_annual_income);
    }
}

async function loadIncome() {
    const result = await apiCall(`get_income&month=${currentMonth}`);
    
    if (result.success && result.data) {
        document.getElementById('basic-salary').value = result.data.basic_salary;
        document.getElementById('allowances').value = result.data.allowances;
        document.getElementById('bonuses').value = result.data.bonuses;
        document.getElementById('other-income').value = result.data.other_income;
        calculateTotalIncome();
    }
}

async function handleIncomeSubmit(e) {
    e.preventDefault();
    
    const data = {
        basic_salary: parseFloat(document.getElementById('basic-salary').value),
        allowances: parseFloat(document.getElementById('allowances').value) || 0,
        bonuses: parseFloat(document.getElementById('bonuses').value) || 0,
        other_income: parseFloat(document.getElementById('other-income').value) || 0,
        month: currentMonth
    };
    
    const result = await apiCall('add_income', 'POST', data);
    
    if (result.success) {
        showToast('Income saved successfully!');
        // Reload income data and recalculate tax
        await loadIncome();
        loadDashboard();
    } else {
        showToast(result.message, true);
    }
}

// ============ EXPENSE FUNCTIONS ============

async function loadExpenses() {
    const result = await apiCall(`get_expenses&month=${currentMonth}`);
    
    if (result.success) {
        const tbody = document.getElementById('expenses-tbody');
        tbody.innerHTML = '';
        
        if (result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No expenses found for this month</td></tr>';
        } else {
            result.data.forEach(expense => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${expense.expense_date}</td>
                    <td>${expense.category_name}</td>
                    <td>${formatCurrency(expense.amount)}</td>
                    <td>${expense.description || '-'}</td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteExpense(${expense.expense_id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
    }
}

async function handleExpenseSubmit(e) {
    e.preventDefault();
    
    const data = {
        category: document.getElementById('expense-category').value,
        amount: parseFloat(document.getElementById('expense-amount').value),
        date: document.getElementById('expense-date').value,
        description: document.getElementById('expense-description').value
    };
    
    const result = await apiCall('add_expense', 'POST', data);
    
    if (result.success) {
        showToast('Expense added successfully!');
        document.getElementById('expense-form').reset();
        document.getElementById('expense-date').valueAsDate = new Date();
        loadExpenses();
        loadDashboard();
    } else {
        showToast(result.message, true);
    }
}

async function deleteExpense(expenseId) {
    if (confirm('Are you sure you want to delete this expense?')) {
        const result = await apiCall(`delete_expense&id=${expenseId}`, 'POST');
        
        if (result.success) {
            showToast('Expense deleted successfully!');
            loadExpenses();
            loadDashboard();
        } else {
            showToast(result.message, true);
        }
    }
}

// ============ BUDGET FUNCTIONS ============

async function loadBudget() {
    const result = await apiCall(`get_budget&month=${currentMonth}`);
    
    if (result.success && result.data.length > 0) {
        result.data.forEach(budget => {
            const input = document.querySelector(`input[name="${budget.category_name}"]`);
            if (input) {
                input.value = budget.allocated_amount;
            }
        });
    }
    
    // Load budget comparison
    await loadBudgetComparison();
}

async function loadBudgetComparison() {
    const result = await apiCall(`get_category_summary&month=${currentMonth}`);
    
    if (result.success) {
        const container = document.getElementById('budget-comparison');
        container.innerHTML = '';
        
        if (result.data.length === 0) {
            container.innerHTML = '<p>Set your budget to see comparison</p>';
        } else {
            result.data.forEach(item => {
                const percentage = Math.min(item.percentage, 100);
                const overBudget = item.percentage > 100 ? 'over-budget' : '';
                
                const div = document.createElement('div');
                div.className = 'budget-item';
                div.innerHTML = `
                    <h4>${item.category}</h4>
                    <p>Budget: ${formatCurrency(item.budget)} | Spent: ${formatCurrency(item.spent)} | Remaining: ${formatCurrency(item.remaining)}</p>
                    <div class="progress-bar">
                        <div class="progress-fill ${overBudget}" style="width: ${percentage}%">
                            ${item.percentage.toFixed(0)}%
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }
    }
}

async function handleBudgetSubmit(e) {
    e.preventDefault();
    
    const budgetInputs = document.querySelectorAll('.budget-input');
    const categories = [];
    
    budgetInputs.forEach(input => {
        categories.push({
            category_name: input.name,
            allocated_amount: parseFloat(input.value) || 0
        });
    });
    
    const data = {
        month: currentMonth,
        categories: categories
    };
    
    const result = await apiCall('set_budget', 'POST', data);
    
    if (result.success) {
        showToast('Budget saved successfully!');
        loadBudgetComparison();
    } else {
        showToast(result.message, true);
    }
}

// ============ SAVINGS GOALS FUNCTIONS ============

async function loadSavingsGoals() {
    const result = await apiCall('get_goals');
    
    if (result.success) {
        const container = document.getElementById('goals-container');
        container.innerHTML = '';
        
        if (result.data.length === 0) {
            container.innerHTML = '<p>No savings goals yet. Add your first goal!</p>';
        } else {
            result.data.forEach(goal => {
                const progress = goal.target_amount > 0 ? (goal.current_amount / goal.target_amount * 100) : 0;
                const progressCapped = Math.min(progress, 100);
                
                const div = document.createElement('div');
                div.className = 'goal-card';
                div.innerHTML = `
                    <h4>${goal.goal_name}</h4>
                    <p><strong>Target:</strong> ${formatCurrency(goal.target_amount)}</p>
                    <p><strong>Current:</strong> ${formatCurrency(goal.current_amount)}</p>
                    <p><strong>Target Date:</strong> ${goal.target_date || 'Not set'}</p>
                    <p><strong>Status:</strong> ${goal.status.toUpperCase()}</p>
                    <div class="goal-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progressCapped}%">
                                ${progress.toFixed(0)}%
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <input type="number" id="goal-amount-${goal.goal_id}" placeholder="Add amount" step="0.01" style="padding: 8px; width: 120px; margin-right: 5px;">
                        <button class="btn btn-primary" onclick="updateGoal(${goal.goal_id})" style="padding: 8px 15px;">Update</button>
                        <button class="btn" onclick="deleteGoal(${goal.goal_id})" style="padding: 8px 15px; background: #dc3545; color: white; margin-left: 5px;">🗑️ Delete</button>
                    </div>
                `;
                container.appendChild(div);
            });
        }
    }
}

async function handleGoalSubmit(e) {
    e.preventDefault();
    
    const data = {
        goal_name: document.getElementById('goal-name').value,
        target_amount: parseFloat(document.getElementById('goal-target').value),
        target_date: document.getElementById('goal-date').value || null
    };
    
    const result = await apiCall('add_goal', 'POST', data);
    
    if (result.success) {
        showToast('Savings goal added successfully!');
        document.getElementById('goal-form').reset();
        loadSavingsGoals();
    } else {
        showToast(result.message, true);
    }
}

async function updateGoal(goalId) {
    const amountInput = document.getElementById(`goal-amount-${goalId}`);
    const amount = parseFloat(amountInput.value);
    
    if (!amount || amount <= 0) {
        showToast('Please enter a valid amount', true);
        return;
    }
    
    const data = {
        goal_id: goalId,
        current_amount: amount,
        status: 'active'
    };
    
    const result = await apiCall('update_goal', 'POST', data);
    
    if (result.success) {
        showToast('Goal updated successfully!');
        loadSavingsGoals();
    } else {
        showToast(result.message, true);
    }
}

async function deleteGoal(goalId) {
    if (!confirm('Are you sure you want to delete this savings goal?')) {
        return;
    }
    
    const result = await apiCall(`delete_goal&goal_id=${goalId}`, 'GET');
    
    if (result.success) {
        showToast('Savings goal deleted successfully!');
        loadSavingsGoals();
    } else {
        showToast(result.message, true);
    }
}

// ============ DASHBOARD FUNCTIONS ============

async function loadDashboard() {
    const result = await apiCall(`get_dashboard&month=${currentMonth}`);
    
    if (result.success) {
        const data = result.data;
        document.getElementById('dash-income').textContent = formatCurrency(data.total_income);
        document.getElementById('dash-expenses').textContent = formatCurrency(data.total_expenses);
        document.getElementById('dash-tax').textContent = formatCurrency(data.monthly_tax);
        document.getElementById('dash-savings').textContent = formatCurrency(data.savings);
        
        // Load expense chart
        await loadExpenseChart();
    }
}

async function loadExpenseChart() {
    const result = await apiCall(`get_monthly_report&month=${currentMonth}`);
    
    if (result.success && result.data.length > 0) {
        const labels = result.data.map(item => item.category_name);
        const data = result.data.map(item => item.total);
        
        const ctx = document.getElementById('expenseChart').getContext('2d');
        
        if (expenseChart) {
            expenseChart.destroy();
        }
        
        expenseChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#43e97b',
                        '#fa709a',
                        '#fee140',
                        '#30cfd0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            padding: 14
                        }
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }
}

// ============ REPORTS FUNCTIONS ============

async function loadReports() {
    await loadMonthlyReportChart();
    await loadCategoryReport();
}

async function loadMonthlyReportChart() {
    const result = await apiCall(`get_monthly_report&month=${currentMonth}`);
    
    if (result.success && result.data.length > 0) {
        const labels = result.data.map(item => item.category_name);
        const data = result.data.map(item => item.total);
        
        const ctx = document.getElementById('monthlyReportChart').getContext('2d');
        
        if (monthlyReportChart) {
            monthlyReportChart.destroy();
        }
        
        monthlyReportChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Expenses (NPR)',
                    data: data,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

async function loadCategoryReport() {
    const result = await apiCall(`get_category_summary&month=${currentMonth}`);
    
    if (result.success) {
        const tbody = document.getElementById('category-report-tbody');
        tbody.innerHTML = '';
        
        if (result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No data available</td></tr>';
        } else {
            result.data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.category}</td>
                    <td>${formatCurrency(item.budget)}</td>
                    <td>${formatCurrency(item.spent)}</td>
                    <td>${formatCurrency(item.remaining)}</td>
                    <td>${item.percentage.toFixed(1)}%</td>
                `;
                tbody.appendChild(row);
            });
        }
    }
}
