<?php
session_start();

// Initialize variables
$display = '0';
$expression = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Retrieve or initialize expression from session
        $expression = isset($_SESSION['expression']) ? $_SESSION['expression'] : '';

        switch ($action) {
            case 'C':
                // Clear everything
                $expression = '';
                $display = '0';
                break;
                
            case '=':
                // Evaluate expression
                $expression = rtrim($expression, '+-*/');
                if (!empty($expression)) {
                    // Validate and calculate
                    if (preg_match('/^[\d+\-*\/\.()]+$/', $expression)) {
                        try {
                            eval("\$result = $expression;");
                            $display = (float)$result == (int)$result ? (int)$result : $result;
                        } catch (Throwable $e) {
                            $display = 'Error';
                        }
                    } else {
                        $display = 'Error';
                    }
                }
                $expression = '';
                break;
                
            case '.':
                // Handle decimal point
                $lastNumber = preg_split('/[+\-*\/]/', $expression);
                $lastNumber = end($lastNumber);
                if (strpos($lastNumber, '.') === false) {
                    if (empty($lastNumber)) {
                        $expression .= '0.';
                    } else {
                        $expression .= '.';
                    }
                }
                $display = $expression ?: '0';
                break;
                
            case '+':
            case '-':
            case '*':
            case '/':
                // Handle operators
                if (empty($expression) && $action !== '-') break;
                
                $lastChar = substr($expression, -1);
                if (in_array($lastChar, ['+', '-', '*', '/'])) {
                    if ($action === '-' && $lastChar !== '-') {
                        $expression .= '-';
                    } else {
                        $expression = substr($expression, 0, -1) . $action;
                    }
                } else {
                    $expression .= $action;
                }
                $display = $expression;
                break;
                
            default:
                // Numbers
                $expression .= $action;
                $display = $expression;
        }
        
        // Update session with current expression
        $_SESSION['expression'] = $expression;
    }
} else {
    session_unset();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Calculator</title>
    <style>
        .calculator {
            width: 300px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        input[type="text"] {
            width: 100%;
            height: 40px;
            margin-bottom: 10px;
            text-align: right;
            padding: 5px;
            font-size: 18px;
        }
        .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
        }
        button {
            padding: 15px;
            font-size: 18px;
            border: 1px solid #ccc;
            border-radius: 3px;
            background-color: #fff;
            cursor: pointer;
        }
        button:hover {
            background-color: #e6e6e6;
        }
        .equals {
            background-color: #4CAF50;
            color: white;
        }
        .clear {
            background-color: #f44336;
            color: white;
        }
    </style>
</head>
<body>
    <div class="calculator">
        <form method="post">
            <input type="text" value="<?php echo $display; ?>" readonly>
            <div class="buttons">
                <button type="submit" name="action" value="7">7</button>
                <button type="submit" name="action" value="8">8</button>
                <button type="submit" name="action" value="9">9</button>
                <button type="submit" name="action" value="/">/</button>
                
                <button type="submit" name="action" value="4">4</button>
                <button type="submit" name="action" value="5">5</button>
                <button type="submit" name="action" value="6">6</button>
                <button type="submit" name="action" value="*">×</button>
                
                <button type="submit" name="action" value="1">1</button>
                <button type="submit" name="action" value="2">2</button>
                <button type="submit" name="action" value="3">3</button>
                <button type="submit" name="action" value="-">-</button>
                
                <button type="submit" name="action" value="0">0</button>
                <button type="submit" name="action" value=".">.</button>
                <button type="submit" name="action" value="C" class="clear">C</button>
                <button type="submit" name="action" value="+" class="equals">+</button>
                
                <button type="submit" name="action" value="=" class="equals" style="grid-column: span 4;">=</button>
            </div>
        </form>
    </div>
</body>
</html>