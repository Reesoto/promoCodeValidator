# Promo Code Validator



## Install the project

Clone this repo on your laptop and execute this : 

```
docker-compose build -d
docker-compose up -d
```

To test the command line, please make it throw your docker's container
```
docker exec -it www_promocode_sf bash
cd promoCodeValidator

php bin/console promo-code:validate
```

You will have a prompt to type your promo code.

If your promo code exists and is valid, it will create a json file in public/jsonFiles directory.
