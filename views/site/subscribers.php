<h1>Абоненты</h1>
<ol>
    <?php
    foreach ($subscribers as $subscriber) {
        echo '<li>' . $subscriber->first_name . ' ' . $subscriber->last_name . ' ' . $subscriber->patronymic . '</li>';
    }
    ?>
</ol>