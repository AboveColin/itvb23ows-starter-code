FROM python:3.8-slim

WORKDIR /

COPY requirements.txt .

RUN pip install -r requirements.txt

COPY . .

CMD ["flask", "--app", "app", "run", "--host=0.0.0.0"]
