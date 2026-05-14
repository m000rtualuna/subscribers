<?php

namespace Controller;

use Src\View;

class Site
{
    public function index(): string
    {
        $view = new View();
        return $view->render('site.hello', ['message' => 'index working']);
    }

    public function hello(): string
    {
        return new View('site.hello', ['message' => 'hello working']);
    }

    public function subscribers(): string
    {
        $message = '';

        $counts = [];
        $subdivisions = Subdivision::withCount('subscribers')->get();
        $telephones = Telephone::whereNull('subscriber_id')->get();
        $rooms = Room::withCount('subscribers')->get();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die('Ошибка безопасности: неверный CSRF‑токен');
            }

            $request = new Request($_POST);
            $data = $request->all();

            $rules = [
                'subscriber_name' => ['required', 'lang'],
                'subscriber_surname' => ['required', 'lang'],
                'subscriber_patronymic' => ['required', 'lang'],
                'subscriber_date_of_birth' => ['required'],
                'subscriber_subdivision_id' => ['required'],
            ];

            $messages = [
                'required' => 'Поле :field не заполнено',
                'lang' => 'Поле :field должно содержать только кириллицу',
            ];

            $validator = new Validator($data, $rules, $messages);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $errorMessage = '';
                foreach ($errors as $field => $msgs) {
                    foreach ($msgs as $msg) {
                        $errorMessage .= $msg . '<br>';
                    }
                }
                $message = rtrim($errorMessage, '<br>');
            } else {
                try {
                    $name = trim($data['subscriber_name']);
                    $surname = trim($data['subscriber_surname']);
                    $patronymic = trim($data['subscriber_patronymic']);
                    $date_of_birth = trim($data['subscriber_date_of_birth']);
                    $subdivision_id = (int)$data['subscriber_subdivision_id'];

                    $subscriber = Subscriber::create([
                        'name' => $name,
                        'surname' => $surname,
                        'patronymic' => $patronymic,
                        'date_of_birth' => $date_of_birth,
                        'subdivision_id' => $subdivision_id,
                    ]);

                    if (isset($_POST['phone_ids']) && is_array($_POST['phone_ids'])) {
                        $selectedPhoneIds = array_map('intval', $_POST['phone_ids']);

                        $availablePhones = Telephone::whereIn('id', $selectedPhoneIds)
                            ->whereNull('subscriber_id')
                            ->pluck('id')
                            ->toArray();

                        if (empty($availablePhones)) {
                            die('Выбранные номера уже заняты');
                        }

                        Telephone::whereIn('id', $availablePhones)
                            ->update(['subscriber_id' => $subscriber->id]);
                    }

                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;

                } catch (\Exception $e) {
                    error_log('Ошибка создания абонента: ' . $e->getMessage());
                    error_log('Трассировка: ' . $e->getTraceAsString());
                    die('Произошла ошибка при создании абонента.');
                }
            }
        }

        $phonesByDepartment = [];
        if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
            $departmentId = (int)$_GET['department_id'];
            $phonesByDepartment = Subscriber::with(['telephone', 'subdivision'])
                ->where('subdivision_id', $departmentId)
                ->get();
        }

        return (new View())->render('site.subscriber', [
            'subscribers' => $subscribers,
            'counts' => $counts,
            'subdivisions' => $subdivisions,
            'telephones' => $telephones,
            'phonesByDepartment' => $phonesByDepartment,
            'rooms' => $rooms,
            'message' => $message,
        ]);
    }

}