<?php

use Framework\Database\Database;
use Framework\Http\Route\Route;
use Framework\Content\Content;
use Framework\Http\Response;
use Framework\Http\Request;
use Framework\Content\Seo;

function stripAccents(string $input): string
{
    //Unwanted array
    $unwantedArray = ['Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'ü' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'];

    //Filter the input
    $cleanString = strtr($input, $unwantedArray);

    //Return the input
    return $cleanString;
}

function randomString(int $length = 15): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

function clearInjections(string|array|null $value): string|array|null
{
    // kijk of input value een array is
    if (is_array($value)) {
        // ga door alle keys/values heen
        foreach ($value as $key => $val) {
            // clear voor elke value/array de values
            $value[$key] = clearInjections($val);
        }
        // return array
        return $value;
    }

    return htmlspecialchars(trim($value), ENT_QUOTES, 'utf-8');
}

function dd($value): void
{
    echo "<pre>";
    print_r($value);
    echo "</pre>";
}

function seo(): Seo
{
    global $seo;
    return ($seo instanceof Seo) ? $seo : new Seo();
}

function request(): Request
{
    global $request;
    return ($request instanceof Request) ? $request : new Request();
}

function response(): Response
{
    global $response;
    return ($response instanceof Response) ? $response : new Response();
}

function content(): Content
{
    global $content;
    return ($content instanceof Content) ? $content : new Content();
}

function route(): Route
{
    global $route;
    return ($route instanceof Route) ? $route : new Route();
}

function DB(): Database
{
    global $DB;
    return ($DB instanceof Database) ? $DB : new Database();
}
