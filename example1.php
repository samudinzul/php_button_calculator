<?php
session_start();

if (!isset($_SESSION['display'])) {
    $_SESSION['display'] = '';
}

if (!isset($_SESSION['error'])) {
    $_SESSION['error'] = '';
}

if (isset($_POST['btn'])) {
    $button = $_POST['btn'];
    $mul_div_operators = ['*', '/'];
    
    switch($button) {
        case 'C':
            $_SESSION['display'] = '';
            $_SESSION['error'] = '';
            break;
        case 'backspace':
            $_SESSION['display'] = substr($_SESSION['display'], 0, -1);
            $_SESSION['error'] = '';
            break;
        case '=':
            try {
                if ($_SESSION['error'] !== '') {
                    break;
                }
                $expression = $_SESSION['display'];
                $expression = preg_replace_callback('/\-+/', function($matches) {
                    return (strlen($matches[0]) % 2 == 0) ? '+' : '-';
                }, $expression);
                
                $result = eval('return ' . $expression . ';');
                $_SESSION['display'] = $result;
            } catch (Exception $e) {
                $_SESSION['display'] = 'Error';
            }
            break;
        default:
            if (in_array($button, $mul_div_operators)) {
                $lastChar = substr($_SESSION['display'], -1);
                
                if ($lastChar === '-') {
                    $_SESSION['error'] = "Missing operand before '$button'";
                    break;
                }
                
                if (in_array($lastChar, $mul_div_operators)) {
                    $_SESSION['display'] = substr($_SESSION['display'], 0, -1) . $button;
                    $_SESSION['error'] = '';
                } else {
                    $_SESSION['display'] .= $button;
                    $_SESSION['error'] = '';
                }
            } else {
                $_SESSION['display'] .= $button;
                $_SESSION['error'] = '';
            }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Calculator</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f0f0f0;
        }
        .calculator {
            width: 300px;
            background: #f4f4f4;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .display {
            width: 100%;
            height: 50px;
            margin-bottom: 5px;
            font-size: 24px;
            text-align: right;
            padding: 5px;
            background: white;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        .error {
            color: #f44336;
            font-size: 14px;
            margin: 5px 0 10px;
            min-height: 20px;
            text-align: right;
        }
        .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        .btn {
            padding: 20px;
            font-size: 20px;
            border: none;
            background: #e0e0e0;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #d0d0d0;
        }
        .operator {
            background: #ff9800;
            color: white;
        }
        .operator:hover {
            background: #f57c00;
        }
        .equals {
            background: #4CAF50;
            color: white;
        }
        .equals:hover {
            background: #388E3C;
        }
        .backspace {
            background: #f44336;
            color: white;
        }
        .backspace:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="calculator">
        <form method="post">
            <input type="text" class="display" value="<?php echo $_SESSION['display']; ?>" readonly>
            <div class="error"><?php echo $_SESSION['error']; ?></div>
            <div class="buttons">
                <button type="submit" name="btn" value="7" class="btn">7</button>
                <button type="submit" name="btn" value="8" class="btn">8</button>
                <button type="submit" name="btn" value="9" class="btn">9</button>
                <button type="submit" name="btn" value="/" class="btn operator">÷</button>
                
                <button type="submit" name="btn" value="4" class="btn">4</button>
                <button type="submit" name="btn" value="5" class="btn">5</button>
                <button type="submit" name="btn" value="6" class="btn">6</button>
                <button type="submit" name="btn" value="*" class="btn operator">×</button>
                
                <button type="submit" name="btn" value="1" class="btn">1</button>
                <button type="submit" name="btn" value="2" class="btn">2</button>
                <button type="submit" name="btn" value="3" class="btn">3</button>
                <button type="submit" name="btn" value="-" class="btn operator">-</button>
                
                <button type="submit" name="btn" value="0" class="btn">0</button>
                <button type="submit" name="btn" value="." class="btn">.</button>
                <button type="submit" name="btn" value="backspace" class="btn backspace">⌫</button>
                <button type="submit" name="btn" value="+" class="btn operator">+</button>

                <button type="submit" name="btn" value="C" class="btn operator" style="grid-column: span 2;">C</button>
                <button type="submit" name="btn" value="=" class="btn equals" style="grid-column: span 2;">=</button>
            </div>
        </form>
    </div>
</body>
</html>
