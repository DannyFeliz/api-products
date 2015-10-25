<?php
use Phalcon\Http\Response;

// Display home view
$app->get('/', function () use ($app) {
    echo $app['view']->render('index');
});

// Retrieves all products
$app->get('/products', function () use ($app) {
    $response = new Response();
    $response->setContentType("application/json", "utf-8");

    $limit = $app->request->get("limit", "int");
    // Limits the products list if the limit parameter exists
    $query = !!$limit ? "SELECT * FROM Products LIMIT {$limit}" : "SELECT * FROM Products";
    $products = $app->modelsManager->executeQuery($query);

    $productsList = [];
    foreach ($products as $product) {
        $productsList[] = [
            'id'          => $product->id,
            'title'       => $product->title,
            'category'    => $product->category,
            'description' => $product->description
        ];
    }

    // Sort products by specified key
    $sort = $app->request->get("sort", "string");
    if ($sort) {
        $sortArray = [];
        $sortType = $app->request->get("asc", "string");
        foreach ($productsList as $product) {
            foreach ($product as $key => $value) {
                if (!isset($sortArray[$key])) {
                    $sortArray[$key] = [];
                }
                $sortArray[$key][] = $value;
            }
        }
        array_multisort($sortArray[$sort], !!$sortType ? SORT_ASC : SORT_DESC, $productsList);
    }

    return $response->setJsonContent($productsList);
});

// Gets a product by id
$app->get('/products/{id:[0-9]+}', function ($id) use ($app) {
    $response = new Response();
    $response->setContentType("application/json", "utf-8");

    $data = [];
    $query = "SELECT * FROM Products WHERE id = :id:";
    $products = $app->modelsManager->executeQuery($query, ["id" => $id]);
    if (count($products) < 1) {
        return $response->setJsonContent([
            "status" => "NOT-FOUND"
        ]);
    } else {
        foreach ($products as $product) {
            $data[] = [
                'id'          => $product->id,
                'title'       => $product->title,
                'category'    => $product->category,
                'description' => $product->description
            ];
        }

        return $response->setJsonContent($data);
    }
});

// Creates a product
$app->post("/products", function () use ($app) {
    $response = new Response();
    $response->setContentType("application/json", "utf-8");

    $product = $app->request->getJsonRawBody();
    $query = "INSERT INTO Products (title, category, description) VALUES (:title:, :category:, :description:)";
    $status = $app->modelsManager->executeQuery($query, [
        "title"       => $product->title,
        "category"    => $product->category,
        "description" => $product->description
    ]);

    if ($status->success()) {
        $response->setStatusCode("201", "Created");
        $product->id = $status->getModel()->id;
        $response->setJsonContent([
            "status" => "OK",
            "data"   => $product
        ]);
    } else {
        // Change the HTTP status
        $response->setStatusCode(409, "Conflict");

        // Send errors to the client
        $errors = [];
        foreach ($status->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }

        $response->setJsonContent(
            [
                'status'   => 'ERROR',
                'messages' => $errors
            ]
        );
    }

    return $response;

});

// Deletes a product
$app->delete("/products/delete/{id:[0-9]+}", function ($id) use ($app) {
    $response = new Response();
    $response->setContentType("application/json", "utf-8");

    $findProduct = "SELECT * FROM Products WHERE id = :id:";
    $productExists = $app->modelsManager->executeQuery($findProduct, ["id" => $id]);
    if (count($productExists) < 1) {
        $response->setStatusCode(404);
        $response->setJsonContent([
            "status" => "NOT-FOUND"
        ]);

        return $response;
    }

    $query = "DELETE FROM Products WHERE id = :id:";
    $status = $app->modelsManager->executeQuery($query, ["id" => $id]);

    if ($status->success()) {
        $response->setStatusCode(200);
        $response->setJsonContent(
            ["status" => "OK"]
        );
    } else {
        $response->setStatusCode("409", "Conflict");
        $response->setJsonContent([
            "status" => "Error"
        ]);
    }

    return $response;

});

// Updates a complete product
$app->put("/products/update/{id:[0-9]+}", function ($id) use ($app) {
    $response = new Response();
    $response->setContentType("application/json", "utf-8");

    $product = $app->request->getJsonRawBody();

    $findProduct = "SELECT * FROM Products WHERE id = :id:";
    $productExists = $app->modelsManager->executeQuery($findProduct, ["id" => $id]);
    if (count($productExists) < 1) {
        $response->setStatusCode(404);
        $response->setJsonContent([
            "status" => "NOT-FOUND"
        ]);

        return $response;
    }

    $query = "UPDATE Products SET title = :title:, category = :category:, description = :description: WHERE id = :id:";
    $status = $app->modelsManager->executeQuery($query,
        [
            "id"          => $id,
            "title"       => $product->title,
            "category"    => $product->category,
            "description" => $product->description
        ]
    );

    if ($status->success()) {
        $response->setStatusCode(200);
        $response->setJsonContent(
            ["status" => "OK"]
        );
    } else {
        $response->setStatusCode(406, "Conflits");
        $response->setJsonContent(
            ["status" => "Error"]
        );
    }

    return $response;

});

// Updates a product partially
$app->patch("/products/update/{id:[0-9]+}", function ($id) use ($app) {
    $response = new Response();
    $response->setContentType("application/json", "utf-8");

    // The true flag coverts the JSON request in an associative array
    $product = $app->request->getJsonRawBody(true);

    $findProduct = "SELECT * FROM Products WHERE id = :id:";
    $productExists = $app->modelsManager->executeQuery($findProduct, ["id" => $id]);
    if (count($productExists) < 1) {
        $response->setStatusCode(404);
        $response->setJsonContent([
            "status" => "NOT-FOUND"
        ]);

        return $response;
    }


    $fields = "";
    $quantity = count($product);
    $index = 1;
    $bindParameters = ["id" => $id];
    // Generates the update fields and bind parameters
    foreach ($product as $key => $prod) {
        $bindParameters[$key] = $prod;
        if ($index == $quantity) {
            $fields .= " {$key} = :$key:";
        } else {
            $fields .= " {$key} = :$key:, ";
        }
        $index++;
    }
    $query = "UPDATE Products SET $fields  WHERE id = :id:";

    $status = $app->modelsManager->executeQuery($query, $bindParameters);

    if ($status->success()) {
        $response->setStatusCode(200);
        $response->setJsonContent(
            ["status" => "OK"]
        );
    } else {
        $response->setStatusCode(406, "Conflits");
        $response->setJsonContent(
            ["status" => "Error"]
        );
    }

    return $response;

});

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo $app['view']->render('404');
});
