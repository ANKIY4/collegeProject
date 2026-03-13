<?php
// Check authentication before loading dashboard
require_once 'session_handler.php';
requireAuth(); // Redirect to auth.html if not logged in

// Get current user
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Budget & Finance Manager - Nepal</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="header-content">
                <div class="header-title">
                    <h1>Personal Budget & Finance Manager</h1>
                    <p class="subtitle">Track your expenses, save money, and manage taxes - Nepal Edition</p>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Welcome, <strong id="user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></strong>!</span>
                    <button class="btn-logout" onclick="handleLogout()">Logout</button>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-tabs">
            <button class="nav-tab active" onclick="showSection('dashboard')">Dashboard</button>
            <button class="nav-tab" onclick="showSection('income')">Income</button>
            <button class="nav-tab" onclick="showSection('expenses')">Expenses</button>
            <button class="nav-tab" onclick="showSection('budget')">Budget</button>
            <button class="nav-tab" onclick="showSection('goals')">Savings Goals</button>
            <button class="nav-tab" onclick="showSection('reports')">Reports</button>
        </nav>

        <!-- Month Selector -->
        <div class="month-selector">
            <label for="current-month">Select Month:</label>
            <input type="month" id="current-month" value="">
        </div>

        <!-- Dashboard Section -->
        <section id="dashboard" class="section active">
            <h2>Dashboard Overview</h2>
            
            <div class="cards-grid">
                <div class="card card-income">
                    <h3>Total Income</h3>
                    <p class="amount" id="dash-income">NPR 0.00</p>
                </div>
                <div class="card card-expenses">
                    <h3>Total Expenses</h3>
                    <p class="amount" id="dash-expenses">NPR 0.00</p>
                </div>
                <div class="card card-tax">
                    <h3>Monthly Tax</h3>
                    <p class="amount" id="dash-tax">NPR 0.00</p>
                </div>
                <div class="card card-savings">
                    <h3>Net Savings</h3>
                    <p class="amount" id="dash-savings">NPR 0.00</p>
                </div>
            </div>

            <div class="dashboard-charts">
                <div class="chart-container">
                    <h3>Expense Breakdown</h3>
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Income Section -->
        <section id="income" class="section">
            <h2>Monthly Income</h2>
            
            <div class="form-card">
                <form id="income-form">
                    <div class="form-group">
                        <label for="basic-salary">Basic Salary (NPR):</label>
                        <input type="number" id="basic-salary" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="allowances">Allowances (NPR):</label>
                        <input type="number" id="allowances" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="bonuses">Bonuses (NPR):</label>
                        <input type="number" id="bonuses" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="other-income">Other Income (NPR):</label>
                        <input type="number" id="other-income" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Total Income:</label>
                        <p class="calculated-value" id="total-income-display">NPR 0.00</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Income</button>
                </form>
            </div>

            <div class="info-card">
                <h3>Tax Information</h3>
                <div id="tax-info">
                    <p><strong>Annual Income:</strong> <span id="annual-income">NPR 0.00</span></p>
                    <p><strong>Income Tax:</strong> <span id="income-tax">NPR 0.00</span></p>
                    <p><strong>SSF Contribution (5.5%):</strong> <span id="ssf-contribution">NPR 0.00</span></p>
                    <p><strong>Total Tax:</strong> <span id="total-tax">NPR 0.00</span></p>
                    <p><strong>Net Annual Income:</strong> <span id="net-annual">NPR 0.00</span></p>
                </div>
            </div>
        </section>

        <!-- Expenses Section -->
        <section id="expenses" class="section">
            <h2>Track Expenses</h2>
            
            <div class="form-card">
                <form id="expense-form">
                    <div class="form-group">
                        <label for="expense-category">Category:</label>
                        <select id="expense-category" required>
                            <option value="">-- Select Category --</option>
                            <option value="Food">Food</option>
                            <option value="Rent">Rent</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Education">Education</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expense-amount">Amount (NPR):</label>
                        <input type="number" id="expense-amount" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="expense-date">Date:</label>
                        <input type="date" id="expense-date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="expense-description">Description:</label>
                        <input type="text" id="expense-description" placeholder="Optional">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </form>
            </div>

            <div class="expenses-list">
                <h3>Recent Expenses</h3>
                <table id="expenses-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="expenses-tbody">
                        <!-- Expenses will be loaded here -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Budget Section -->
        <section id="budget" class="section">
            <h2>Set Monthly Budget</h2>
            
            <div class="form-card">
                <form id="budget-form">
                    <div class="budget-categories">
                        <div class="form-group">
                            <label>Food:</label>
                            <input type="number" name="Food" class="budget-input" step="0.01" value="10000">
                        </div>
                        <div class="form-group">
                            <label>Rent:</label>
                            <input type="number" name="Rent" class="budget-input" step="0.01" value="15000">
                        </div>
                        <div class="form-group">
                            <label>Utilities:</label>
                            <input type="number" name="Utilities" class="budget-input" step="0.01" value="3000">
                        </div>
                        <div class="form-group">
                            <label>Transportation:</label>
                            <input type="number" name="Transportation" class="budget-input" step="0.01" value="5000">
                        </div>
                        <div class="form-group">
                            <label>Entertainment:</label>
                            <input type="number" name="Entertainment" class="budget-input" step="0.01" value="2000">
                        </div>
                        <div class="form-group">
                            <label>Healthcare:</label>
                            <input type="number" name="Healthcare" class="budget-input" step="0.01" value="3000">
                        </div>
                        <div class="form-group">
                            <label>Education:</label>
                            <input type="number" name="Education" class="budget-input" step="0.01" value="5000">
                        </div>
                        <div class="form-group">
                            <label>Others:</label>
                            <input type="number" name="Others" class="budget-input" step="0.01" value="2000">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Budget</button>
                </form>
            </div>

            <div class="budget-summary">
                <h3>Budget vs Actual Spending</h3>
                <div id="budget-comparison">
                    <!-- Budget comparison will be loaded here -->
                </div>
            </div>
        </section>

        <!-- Savings Goals Section -->
        <section id="goals" class="section">
            <h2>Savings Goals</h2>
            
            <div class="form-card">
                <form id="goal-form">
                    <div class="form-group">
                        <label for="goal-name">Goal Name:</label>
                        <input type="text" id="goal-name" placeholder="e.g., Emergency Fund" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="goal-target">Target Amount (NPR):</label>
                        <input type="number" id="goal-target" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="goal-date">Target Date:</label>
                        <input type="date" id="goal-date">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Goal</button>
                </form>
            </div>

            <div class="goals-list">
                <h3>Your Savings Goals</h3>
                <div id="goals-container">
                    <!-- Goals will be loaded here -->
                </div>
            </div>
        </section>

        <!-- Reports Section -->
        <section id="reports" class="section">
            <h2>Financial Reports</h2>
            
            <div class="report-card">
                <h3>Monthly Expense Report</h3>
                <canvas id="monthlyReportChart"></canvas>
            </div>

            <div class="report-card">
                <h3>Category-wise Analysis</h3>
                <table id="category-report-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Budget</th>
                            <th>Spent</th>
                            <th>Remaining</th>
                            <th>% Used</th>
                        </tr>
                    </thead>
                    <tbody id="category-report-tbody">
                        <!-- Category report will be loaded here -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Toast Notification -->
        <div id="toast" class="toast"></div>
    </div>

    <script>
        // Set user data from PHP
        window.userData = {
            full_name: <?php echo json_encode($currentUser['full_name']); ?>,
            email: <?php echo json_encode($currentUser['email']); ?>,
            user_id: <?php echo json_encode($currentUser['user_id']); ?>
        };
    </script>
    <script src="script.js"></script>
</body>
</html>
