<?php
// Personal Budget and Finance Manager - Backend API
header('Content-Type: application/json');

require_once 'config.php';
require_once 'session_handler.php';

// Check authentication for all API calls
apiRequireAuth();

// Get current user ID from session
$current_user_id = getCurrentUserId();

// Get database connection
$conn = getDBConnection();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Route requests based on action
switch ($action) {
    // Income Management
    case 'add_income':
        addIncome($conn, $current_user_id);
        break;
    case 'get_income':
        getIncome($conn, $current_user_id);
        break;
    case 'update_income':
        updateIncome($conn, $current_user_id);
        break;
    
    // Expense Management
    case 'add_expense':
        addExpense($conn, $current_user_id);
        break;
    case 'get_expenses':
        getExpenses($conn, $current_user_id);
        break;
    case 'delete_expense':
        deleteExpense($conn, $current_user_id);
        break;
    case 'update_expense':
        updateExpense($conn, $current_user_id);
        break;
    
    // Budget Management
    case 'set_budget':
        setBudget($conn, $current_user_id);
        break;
    case 'get_budget':
        getBudget($conn, $current_user_id);
        break;
    
    // Savings Goals
    case 'add_goal':
        addSavingsGoal($conn, $current_user_id);
        break;
    case 'get_goals':
        getSavingsGoals($conn, $current_user_id);
        break;
    case 'update_goal':
        updateSavingsGoal($conn, $current_user_id);
        break;
    case 'delete_goal':
        deleteSavingsGoal($conn, $current_user_id);
        break;
    
    // Tax Calculation
    case 'calculate_tax':
        calculateTax($conn);
        break;
    
    // Dashboard/Reports
    case 'get_dashboard':
        getDashboard($conn, $current_user_id);
        break;
    case 'get_monthly_report':
        getMonthlyReport($conn, $current_user_id);
        break;
    case 'get_category_summary':
        getCategorySummary($conn, $current_user_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();

// ============ INCOME FUNCTIONS ============

function addIncome($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $basic_salary = $data['basic_salary'] ?? 0;
    $allowances = $data['allowances'] ?? 0;
    $bonuses = $data['bonuses'] ?? 0;
    $other_income = $data['other_income'] ?? 0;
    $month = $data['month'] ?? date('Y-m');
    
    $total_income = $basic_salary + $allowances + $bonuses + $other_income;
    
    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing records
    $stmt = $conn->prepare("INSERT INTO income (user_id, month, basic_salary, allowances, bonuses, other_income, total_income) 
                            VALUES (?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            basic_salary = VALUES(basic_salary), 
                            allowances = VALUES(allowances), 
                            bonuses = VALUES(bonuses), 
                            other_income = VALUES(other_income), 
                            total_income = VALUES(total_income)");
    $stmt->bind_param("issdddd", $user_id, $month, $basic_salary, $allowances, $bonuses, $other_income, $total_income);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Income saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save income: ' . $conn->error]);
    }
}

function getIncome($conn, $user_id) {
    $month = $_GET['month'] ?? date('Y-m');
    $user_id = $user_id;
    
    $stmt = $conn->prepare("SELECT * FROM income WHERE user_id = ? AND month = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No income data found']);
    }
}

function updateIncome($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $income_id = $data['income_id'];
    $basic_salary = $data['basic_salary'] ?? 0;
    $allowances = $data['allowances'] ?? 0;
    $bonuses = $data['bonuses'] ?? 0;
    $other_income = $data['other_income'] ?? 0;
    
    $total_income = $basic_salary + $allowances + $bonuses + $other_income;
    
    $stmt = $conn->prepare("UPDATE income SET basic_salary = ?, allowances = ?, bonuses = ?, other_income = ?, total_income = ? WHERE income_id = ?");
    $stmt->bind_param("dddddi", $basic_salary, $allowances, $bonuses, $other_income, $total_income, $income_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Income updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update income']);
    }
}

// ============ EXPENSE FUNCTIONS ============

function addExpense($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $category = $data['category'];
    $amount = $data['amount'];
    $date = $data['date'];
    $description = $data['description'] ?? '';
    $user_id = $user_id;
    
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category_name, amount, expense_date, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $user_id, $category, $amount, $date, $description);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense added successfully', 'expense_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add expense']);
    }
}

function getExpenses($conn, $user_id) {
    $month = $_GET['month'] ?? date('Y-m');
    $user_id = $user_id;
    
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ? ORDER BY expense_date DESC");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $expenses]);
}

function deleteExpense($conn, $user_id) {
    $expense_id = $_GET['id'] ?? 0;
    
    $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
    $stmt->bind_param("i", $expense_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete expense']);
    }
}

function updateExpense($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $expense_id = $data['expense_id'];
    $category = $data['category'];
    $amount = $data['amount'];
    $date = $data['date'];
    $description = $data['description'] ?? '';
    
    $stmt = $conn->prepare("UPDATE expenses SET category_name = ?, amount = ?, expense_date = ?, description = ? WHERE expense_id = ?");
    $stmt->bind_param("sdssi", $category, $amount, $date, $description, $expense_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update expense']);
    }
}

// ============ BUDGET FUNCTIONS ============

function setBudget($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $month = $data['month'] ?? date('Y-m');
    $categories = $data['categories']; // Array of {category_name, allocated_amount}
    $user_id = $user_id;
    
    // Delete existing budget for the month
    $stmt = $conn->prepare("DELETE FROM budget_categories WHERE user_id = ? AND month = ?");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    
    // Insert new budget
    $stmt = $conn->prepare("INSERT INTO budget_categories (user_id, month, category_name, allocated_amount) VALUES (?, ?, ?, ?)");
    
    foreach ($categories as $cat) {
        $stmt->bind_param("issd", $user_id, $month, $cat['category_name'], $cat['allocated_amount']);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Budget set successfully']);
}

function getBudget($conn, $user_id) {
    $month = $_GET['month'] ?? date('Y-m');
    $user_id = $user_id;
    
    $stmt = $conn->prepare("SELECT * FROM budget_categories WHERE user_id = ? AND month = ?");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $budget = [];
    while ($row = $result->fetch_assoc()) {
        $budget[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $budget]);
}

// ============ SAVINGS GOALS FUNCTIONS ============

function addSavingsGoal($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $goal_name = $data['goal_name'];
    $target_amount = $data['target_amount'];
    $target_date = $data['target_date'] ?? null;
    $user_id = $user_id;
    
    $stmt = $conn->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, target_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $user_id, $goal_name, $target_amount, $target_date);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Savings goal added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add goal']);
    }
}

function getSavingsGoals($conn, $user_id) {
    $user_id = $user_id;
    
    $stmt = $conn->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $goals = [];
    while ($row = $result->fetch_assoc()) {
        $goals[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $goals]);
}

function updateSavingsGoal($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $goal_id = $data['goal_id'];
    $current_amount = $data['current_amount'];
    $status = $data['status'] ?? 'active';
    
    $stmt = $conn->prepare("UPDATE savings_goals SET current_amount = ?, status = ? WHERE goal_id = ?");
    $stmt->bind_param("dsi", $current_amount, $status, $goal_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Savings goal updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update goal']);
    }
}

function deleteSavingsGoal($conn, $user_id) {
    $goal_id = $_GET['goal_id'] ?? 0;
    
    // Verify the goal belongs to the user before deleting
    $stmt = $conn->prepare("DELETE FROM savings_goals WHERE goal_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Savings goal deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete goal or goal not found']);
    }
}

// ============ TAX CALCULATION ============

function calculateTax($conn) {
    global $TAX_SLABS;
    
    $annual_income = $_GET['income'] ?? 0;
    
    $income_tax = 0;
    $remaining_income = $annual_income;
    
    // Calculate income tax based on Nepal's tax slabs
    foreach ($TAX_SLABS as $slab) {
        if ($remaining_income <= 0) break;
        
        $taxable_in_slab = 0;
        if ($remaining_income > $slab['max']) {
            $taxable_in_slab = $slab['max'] - $slab['min'] + 1;
        } else {
            $taxable_in_slab = $remaining_income - $slab['min'] + 1;
        }
        
        if ($taxable_in_slab > 0) {
            $income_tax += $taxable_in_slab * $slab['rate'];
            $remaining_income -= $taxable_in_slab;
        }
    }
    
    // Calculate SSF (on monthly basic salary)
    $monthly_salary = $annual_income / 12;
    $ssf_monthly = $monthly_salary * EMPLOYEE_SSF_RATE;
    $ssf_annual = $ssf_monthly * 12;
    
    $total_tax = $income_tax + $ssf_annual;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'annual_income' => $annual_income,
            'income_tax' => round($income_tax, 2),
            'ssf_contribution' => round($ssf_annual, 2),
            'total_tax' => round($total_tax, 2),
            'monthly_tax' => round($total_tax / 12, 2),
            'net_annual_income' => round($annual_income - $total_tax, 2),
            'net_monthly_income' => round(($annual_income - $total_tax) / 12, 2)
        ]
    ]);
}

// ============ DASHBOARD & REPORTS ============

function getDashboard($conn, $user_id) {
    $month = $_GET['month'] ?? date('Y-m');
    $user_id = $user_id;
    
    // Get income
    $stmt = $conn->prepare("SELECT total_income FROM income WHERE user_id = ? AND month = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $income_data = $result->fetch_assoc();
    $total_income = $income_data['total_income'] ?? 0;
    
    // Get total expenses
    $stmt = $conn->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $expense_data = $result->fetch_assoc();
    $total_expenses = $expense_data['total_expenses'] ?? 0;
    
    // Calculate tax
    $annual_income = $total_income * 12;
    $tax_data = calculateTaxInternal($annual_income);
    $monthly_tax = $tax_data['monthly_tax'];
    
    // Calculate savings
    $savings = $total_income - $total_expenses - $monthly_tax;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'month' => $month,
            'total_income' => round($total_income, 2),
            'total_expenses' => round($total_expenses, 2),
            'monthly_tax' => round($monthly_tax, 2),
            'savings' => round($savings, 2),
            'savings_percentage' => $total_income > 0 ? round(($savings / $total_income) * 100, 2) : 0
        ]
    ]);
}

function getMonthlyReport($conn, $user_id) {
    $month = $_GET['month'] ?? date('Y-m');
    $user_id = $user_id;
    
    // Get expenses by category
    $stmt = $conn->prepare("SELECT category_name, SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ? GROUP BY category_name");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses_by_category = [];
    while ($row = $result->fetch_assoc()) {
        $expenses_by_category[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $expenses_by_category]);
}

function getCategorySummary($conn, $user_id) {
    $month = $_GET['month'] ?? date('Y-m');
    $user_id = $user_id;
    
    // Get budget and actual expenses by category
    $stmt = $conn->prepare("
        SELECT 
            b.category_name,
            b.allocated_amount as budget,
            COALESCE(SUM(e.amount), 0) as spent
        FROM budget_categories b
        LEFT JOIN expenses e ON b.category_name = e.category_name 
            AND e.user_id = b.user_id 
            AND DATE_FORMAT(e.expense_date, '%Y-%m') = b.month
        WHERE b.user_id = ? AND b.month = ?
        GROUP BY b.category_name, b.allocated_amount
    ");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $summary = [];
    while ($row = $result->fetch_assoc()) {
        $remaining = $row['budget'] - $row['spent'];
        $percentage = $row['budget'] > 0 ? ($row['spent'] / $row['budget']) * 100 : 0;
        
        $summary[] = [
            'category' => $row['category_name'],
            'budget' => round($row['budget'], 2),
            'spent' => round($row['spent'], 2),
            'remaining' => round($remaining, 2),
            'percentage' => round($percentage, 2)
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $summary]);
}

// Helper function for internal tax calculation
function calculateTaxInternal($annual_income) {
    global $TAX_SLABS;
    
    $income_tax = 0;
    $remaining_income = $annual_income;
    
    foreach ($TAX_SLABS as $slab) {
        if ($remaining_income <= 0) break;
        
        $taxable_in_slab = 0;
        if ($remaining_income > $slab['max']) {
            $taxable_in_slab = $slab['max'] - $slab['min'] + 1;
        } else {
            $taxable_in_slab = $remaining_income - $slab['min'] + 1;
        }
        
        if ($taxable_in_slab > 0) {
            $income_tax += $taxable_in_slab * $slab['rate'];
            $remaining_income -= $taxable_in_slab;
        }
    }
    
    $monthly_salary = $annual_income / 12;
    $ssf_monthly = $monthly_salary * EMPLOYEE_SSF_RATE;
    $ssf_annual = $ssf_monthly * 12;
    $total_tax = $income_tax + $ssf_annual;
    
    return [
        'income_tax' => $income_tax,
        'ssf_contribution' => $ssf_annual,
        'total_tax' => $total_tax,
        'monthly_tax' => $total_tax / 12
    ];
}
?>
