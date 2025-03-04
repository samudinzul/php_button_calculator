<?php
session_start();
// Serve CSS if requested
if (isset($_GET['css']) && $_GET['css'] == '1') {
    header("Content-type: text/css");
    readfile("example4.php.css"); // Output the pure CSS file
    exit; // Stop further execution
}

class Calculator
{
    private $expression = '';
    //private $current; 

    public function __construct()
    {
        if (!isset($_SESSION['expression'])) {
            $_SESSION['expression'] = '';
            $_SESSION['display'] = '0';
        }
        $this->expression = $_SESSION['expression'];
    }

    public function processInput($input)
    {
        if ($input === 'decimal') {
            $this->buildExpression('.');
            return $_SESSION['display'];
        }

        if ($input === 'C') {
            $this->clear();
            return '0';
        }

        if ($input === 'CE') {
            $this->clearEntry();
            return $_SESSION['display'];
        }

        if ($input === '=') {
            return $this->calculate();
        }

        $this->buildExpression($input);
        return $_SESSION['display'];
    }

    private function buildExpression($input)
    {
        $operators = ['+', '-', 'x', '/', '*'];

        if ($input === '.') {
            $lastNumber = strrchr($this->expression, '.');
            if (!$lastNumber && !preg_match('/\.\d*$/', $this->expression)) {
                $this->expression .= $input;
            }
            $_SESSION['expression'] = $this->expression;
            $_SESSION['display'] = $this->expression;
            return;
        }

        if ($input === '-') {
            $lastChar = substr($this->expression, -1);
            if ($lastChar === '-') {
                $this->expression .= $input;
            } else if (empty($this->expression) || in_array($lastChar, $operators)) {
                $this->expression .= $input;
            } else {
                while (in_array(substr($this->expression, -1), $operators)) {
                    $this->expression = substr($this->expression, 0, -1);
                }
                $this->expression .= $input;
            }
        } else if (in_array($input, $operators)) {
            if (!empty($this->expression)) {
                while (in_array(substr($this->expression, -1), $operators)) {
                    $this->expression = substr($this->expression, 0, -1);
                }
                $this->expression .= $input;
            }
        } else {
            $this->expression .= $input;
        }

        $_SESSION['expression'] = $this->expression;
        $_SESSION['display'] = $this->expression;
    }

    private function calculate()
    {
        $expr = $this->expression;

        if (!$this->isValidParentheses($expr)) {
            $_SESSION['expression'] = '';
            return 'Expression Error';
        }

        // Add multiplication operator between number and parenthesis
        $expr = preg_replace('/(\d+)(\()/', '$1*$2', $expr);

        $expr = preg_replace('/\-{3,}/', '-', $expr);
        $expr = preg_replace('/\-{2}/', '+', $expr);
        $expr = preg_replace('/\+\-/', '-', $expr);
        $expr = preg_replace('/\-\+/', '-', $expr);

        $expr = str_replace('x', '*', $expr);

        try {
            if (strpos($expr, '-') === 0) {
                $expr = '0' . $expr;
            }

            set_error_handler(function ($errno, $errstr) {
                throw new Exception('Expression Error');
            });

            $result = $this->evaluateExpression($expr);

            restore_error_handler();

            $_SESSION['expression'] = (string)$result;
            $_SESSION['display'] = (string)$result;
            return $result;
        } catch (Exception $e) {
            $_SESSION['expression'] = '';
            return 'Expression Error';
        }
    }

    private function isValidParentheses($expr)
    {
        $stack = 0;
        for ($i = 0; $i < strlen($expr); $i++) {
            if ($expr[$i] === '(') {
                $stack++;
            } else if ($expr[$i] === ')') {
                $stack--;
            }
            if ($stack < 0) return false;
        }
        return $stack === 0;
    }

    private function evaluateExpression($expr)
    {
        if (!preg_match('/^[0-9\+\-\*\/\(\)\.\s]*$/', $expr)) {
            throw new Exception('Expression Error');
        }

        // Insert multiplication operator between consecutive parentheses groups
        $expr = preg_replace('/\)(\()/', ')*(', $expr);

        // Insert multiplication operator between number and opening parenthesis
        $expr = preg_replace('/(\d+)(\()/', '$1*$2', $expr);

        // Insert multiplication operator between closing parenthesis and number
        $expr = preg_replace('/\)(\d+)/', ')*$1', $expr);

        return eval('return ' . $expr . ';');
    }

    private function clear()
    {
        $this->expression = '';
        $_SESSION['expression'] = '';
        $_SESSION['display'] = '0';
    }

    private function clearEntry()
    {
        if (!empty($this->expression)) {
            $this->expression = substr($this->expression, 0, -1);
        }
        $_SESSION['expression'] = $this->expression;
        $_SESSION['display'] = empty($this->expression) ? '0' : $this->expression;
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Advanced Calculator</title>
    <!-- Link to example4.php with a query parameter -->
    <link rel="stylesheet" href="example4.php?css=1">
</head>

<body>
    <?php
    $calculator = new Calculator();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = key($_POST);
        $value = $_POST[$input];
        $display = $calculator->processInput($input);
    }
    ?>

    <div class="calculator">
        <input type="text" class="display" value="<?php echo isset($display) ? $display : '0'; ?>" readonly>
        <div class="buttons">
            <form method="post" style="display: contents;">
                <button type="submit" name="C" class="clear">C</button>
                <button type="submit" name="CE" class="clear">CE</button>
                <button type="submit" name="(" class="operator">(</button>
                <button type="submit" name=")" class="operator">)</button>

                <button type="submit" name="7">7</button>
                <button type="submit" name="8">8</button>
                <button type="submit" name="9">9</button>
                <button type="submit" name="/" class="operator">/</button>

                <button type="submit" name="4">4</button>
                <button type="submit" name="5">5</button>
                <button type="submit" name="6">6</button>
                <button type="submit" name="x" class="operator">×</button>

                <button type="submit" name="1">1</button>
                <button type="submit" name="2">2</button>
                <button type="submit" name="3">3</button>
                <button type="submit" name="-" class="operator">-</button>

                <button type="submit" name="0">0</button>
                <button type="submit" name="decimal" value=".">.</button>
                <button type="submit" name="=" class="equals">=</button>
                <button type="submit" name="+" class="operator">+</button>
            </form>
        </div>
    </div>
</body>

</html>