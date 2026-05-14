<h1>Абоненты</h1>
<ol>
    <?php
    foreach ($subscribers as $subscriber) {
        echo '<li>' . $subscriber->title . '</li>';
    }
    ?>
</ol>