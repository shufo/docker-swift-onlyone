install:
	@echo "Verify no other virtual machines are booting. \n\n"
	vagrant up
	docker-compose up -d
build:
	docker-compose build
logs:
	docker-compose logs 
up:
	docker-compose up -d
rm:
	docker-compose kill && docker-compose rm -f
ps:
	docker-compose ps
restart:
	docker-compose kill && docker-compose rm -f && docker-compose up -d
recreate:
	docker-compose kill && docker-compose rm -f && docker-compose build && docker-compose up -d
