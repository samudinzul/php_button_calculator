<?php
session_start();

class Calculator {
    private $current = '';
    private $expression = '';
    
    public function processInput($input) {
        if ($input === 'C') {
            $this->clear();
            return;
        }
        
        if ($input === '=') {
            $this->calculate();
            return;
        }
        
        if ($input === '⌫') {
            $this->handleBackspace();
            return;
        }
        
        $valid_chars = ['0','1','2','3','4','5','6','7','8','9','.','+','-','*','/'];
        if (!in_array($input, $valid_chars)) return;
        
        if (in_array($input, ['+','*','/'])) {
            $this->handleOperator($input);
            return;
        }
        
        if ($input === '-') {
            $this->handleSubtraction();
            return;
        }
        
        $this->handleNumber($input);
    }
    
    private function clear() {
        $this->current = '';
        $this->expression = '';
    }
    
    private function handleBackspace() {
        if ($this->current !== '') {
            $this->current = substr($this->current, 0, -1);
        } else {
            $this->expression = substr($this->expression, 0, -1);
        }
    }
    
    private function handleNumber($num) {
        if ($num === '.') {
            if (strpos($this->current, '.') === false) {
                $this->current = $this->current ? $this->current . '.' : '0.';
            }
        } else {
            $this->current .= $num;
        }
    }
    
    private function handleSubtraction() {
        $last_char = substr($this->expression, -1);
        
        if (($this->current === '' && empty($this->expression)) || 
            (!empty($this->expression) && in_array($last_char, ['+','*','/']))) {
            $this->current = '-';
        } else {
            $this->handleOperator('-');
        }
    }
    
    private function handleOperator($op) {
        if ($this->current !== '') {
            $this->expression .= $this->current;
            $this->current = '';
        }
        
        $last_char = substr($this->expression, -1);
        if (in_array($last_char, ['+','-','*','/'])) {
            $this->expression = substr($this->expression, 0, -1);
        }
        
        $this->expression .= $op;
    }
    
    private function calculate() {
        if ($this->current !== '') {
            $this->expression .= $this->current;
        }
        
        if (!empty($this->expression)) {
            try {
                $this->current = $this->parseExpression();
            } catch (Exception $e) {
                $this->current = 'Error';
            }
            $this->expression = '';
        }
    }
    
    private function parseExpression() {
        $tokens = [];
        $number = '';
        $chars = str_split($this->expression);
        $prev_char = null;

        foreach ($chars as $char) {
            if (is_numeric($char) || $char === '.') {
                $number .= $char;
            } elseif ($char === '-') {
                if ($number === '' && ($prev_char === null || in_array($prev_char, ['+','-','*','/']))) {
                    $number .= '-';
                } else {
                    if ($number !== '') {
                        $tokens[] = (float)$number;
                        $number = '';
                    }
                    $tokens[] = '-';
                }
            } else {
                if ($number !== '') {
                    $tokens[] = (float)$number;
                    $number = '';
                }
                $tokens[] = $char;
            }
            $prev_char = $char;
        }
        
        if ($number !== '') {
            $tokens[] = (float)$number;
        }

        if (!$this->validateTokens($tokens)) {
            throw new Exception('Invalid expression');
        }

        $i = 0;
        while ($i < count($tokens)) {
            if (in_array($tokens[$i], ['*', '/'])) {
                $left = $tokens[$i-1];
                $right = $tokens[$i+1];
                $result = ($tokens[$i] === '*') ? $left * $right : $left / $right;
                array_splice($tokens, $i-1, 3, $result);
                $i--;
            }
            $i++;
        }

        $result = $tokens[0];
        for ($i = 1; $i < count($tokens); $i += 2) {
            $operator = $tokens[$i];
            $operand = $tokens[$i+1];
            $result = ($operator === '+') ? $result + $operand : $result - $operand;
        }

        return (string)(round($result, 4) == (int)$result ? (int)$result : round($result, 4));
    }
    
    private function validateTokens($tokens) {
        $expect_number = true;
        
        foreach ($tokens as $token) {
            if ($expect_number) {
                if (!is_numeric($token)) return false;
                $expect_number = false;
            } else {
                if (!in_array($token, ['+','-','*','/'])) return false;
                $expect_number = true;
            }
        }
        
        return !$expect_number;
    }
    
    public function getDisplay() {
        $display = $this->expression . $this->current;
        return empty($display) ? '0' : $display;
    }
}

if (!isset($_SESSION['calc'])) {
    $_SESSION['calc'] = new Calculator();
}

$calc = $_SESSION['calc'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $calc->processInput($_POST['action']);
}

$display = $calc->getDisplay();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Calculator with Backspace</title>
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
            box-sizing: border-box;/* align with grid of left and right*/
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
        .operator {
            background-color:rgb(58, 47, 211);
            color: white;
        }
        .clear {
            background-color: #f44336;
            color: white;
        }
        .backspace {
            background-color:rgb(112, 7, 0);
            color: white;
        }
    </style>
</head>
<body>
    <div class="calculator">
        <form method="post">
            <input type="text" value="<?= htmlspecialchars($display) ?>" readonly>
            <div class="buttons">
                <button type="submit" name="action" value="7">7</button>
                <button type="submit" name="action" value="8">8</button>
                <button type="submit" name="action" value="9">9</button>
                <button type="submit" name="action" value="/" class="operator">/</button>
                
                <button type="submit" name="action" value="4">4</button>
                <button type="submit" name="action" value="5">5</button>
                <button type="submit" name="action" value="6">6</button>
                <button type="submit" name="action" value="*" class="operator">×</button>
                
                <button type="submit" name="action" value="1">1</button>
                <button type="submit" name="action" value="2">2</button>
                <button type="submit" name="action" value="3">3</button>
                <button type="submit" name="action" value="-" class="operator">-</button>
                
                <button type="submit" name="action" value="0">0</button>
                <button type="submit" name="action" value=".">.</button>
                <button type="submit" name="action" value="⌫" class="backspace">⌫</button>
                <button type="submit" name="action" value="+" class="operator">+</button>
                
                <button type="submit" name="action" value="=" class="equals" style="grid-column: span 4;">=</button>
                <button type="submit" name="action" value="C" class="clear" style="grid-column: span 4;">C</button>
            </div>
        </form>
    </div>
</body>
</html>