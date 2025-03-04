<?php
session_start();

// Serve CSS if requested
if (isset($_GET['css']) && $_GET['css'] == '1') {
    header("Content-type: text/css");
    readfile("example4.php.css");
    exit;
}

class Calculator
{
    private $expression = '';

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
            // Check if the current number already has a decimal point
            $lastOperatorPos = 0;
            for ($i = strlen($this->expression) - 1; $i >= 0; $i--) {
                if (in_array($this->expression[$i], $operators)) {
                    $lastOperatorPos = $i + 1;
                    break;
                }
            }

            $currentNumber = substr($this->expression, $lastOperatorPos);
            if (strpos($currentNumber, '.') === false) {
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

        $trimmedExpr = trim($expr, ' ()');
        if (empty($trimmedExpr) || $trimmedExpr === '()') {
            $_SESSION['expression'] = '';
            return 'Expression Error';
        }

        // Handle implicit multiplication and replace 'x' with '*'
        $expr = $this->handleImplicitMultiplication($expr);
        $expr = str_replace('x', '*', $expr);

        try {
            if (strpos($expr, '-') === 0) {
                $expr = '0' . $expr;
            }

            $result = $this->evaluateExpression($expr);

            if ($result === 'Infinity') {
                $_SESSION['expression'] = '';
                return 'Infinity';
            }

            $_SESSION['expression'] = (string)$result;
            $_SESSION['display'] = (string)$result;
            return $result;
        } catch (Exception $e) {
            $_SESSION['expression'] = '';
            return 'Expression Error';
        }
    }

    private function handleImplicitMultiplication($expr)
    {
        $newExpr = '';
        for ($i = 0; $i < strlen($expr); $i++) {
            $newExpr .= $expr[$i];
            if ($i + 1 < strlen($expr)) {
                if (is_numeric($expr[$i]) && $expr[$i + 1] === '(') {
                    $newExpr .= '*';
                }
                if ($expr[$i] === ')' && is_numeric($expr[$i + 1])) {
                    $newExpr .= '*';
                }
                if ($expr[$i] === ')' && $expr[$i + 1] === '(') {
                    $newExpr .= '*';
                }
            }
        }
        return $newExpr;
    }

    private function evaluateExpression($expr)
    {
        if ($this->hasDivisionByZero($expr)) {
            return 'Infinity';
        }

        $allowedChars = '0123456789+-*/(). ';
        for ($i = 0; $i < strlen($expr); $i++) {
            if (strpos($allowedChars, $expr[$i]) === false) {
                throw new Exception('Expression Error');
            }
        }

        if (
            $this->hasConsecutiveOperators($expr) ||
            $this->hasInvalidDecimalPoints($expr) ||
            $this->hasTrailingOperator($expr) ||
            $this->hasEmptyParentheses($expr)
        ) {
            throw new Exception('Expression Error');
        }

        try {
            if (!$this->validateExpression($expr)) {
                throw new Exception('Expression Error');
            }

            set_error_handler(function ($errno, $errstr) {
                throw new Exception('Expression Error');
            });

            $result = eval('return ' . $expr . ';');

            restore_error_handler();

            if ($result === false || !is_numeric($result)) {
                throw new Exception('Expression Error');
            }

            return $result;
        } catch (Exception $e) {
            throw new Exception('Expression Error');
        }
    }

    private function hasDivisionByZero($expr)
    {
        $len = strlen($expr);
        for ($i = 0; $i < $len; $i++) {
            if ($expr[$i] === '/') {
                $j = $i + 1;
                while ($j < $len && $expr[$j] === ' ') {
                    $j++;
                }

                if ($j < $len && $expr[$j] === '0') {
                    $nextChar = ($j + 1 < $len) ? $expr[$j + 1] : '';
                    if ($nextChar === '' || strpos('+-*/()', $nextChar) !== false) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function hasConsecutiveOperators($expr)
    {
        $operators = '+-*/';
        for ($i = 0; $i < strlen($expr) - 1; $i++) {
            if (
                strpos($operators, $expr[$i]) !== false &&
                strpos($operators, $expr[$i + 1]) !== false
            ) {
                return true;
            }
        }
        return false;
    }

    private function hasInvalidDecimalPoints($expr)
    {
        $operators = '+-*/()';
        $current = '';

        for ($i = 0; $i < strlen($expr); $i++) {
            if (strpos($operators, $expr[$i]) !== false) {
                if (substr_count($current, '.') > 1) {
                    return true;
                }
                $current = '';
            } else {
                $current .= $expr[$i];
            }
        }
        return substr_count($current, '.') > 1;
    }

    private function hasTrailingOperator($expr)
    {
        $operators = '+-*/';
        return strpos($operators, substr($expr, -1)) !== false;
    }

    private function hasEmptyParentheses($expr)
    {
        return strpos($expr, '()') !== false;
    }

    private function validateExpression($expr)
    {
        $operators = '+-*/()';
        $numbers = '0123456789.';
        $valid = $operators . $numbers . ' ';

        for ($i = 0; $i < strlen($expr); $i++) {
            if (strpos($valid, $expr[$i]) === false) {
                return false;
            }
        }
        return true;
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