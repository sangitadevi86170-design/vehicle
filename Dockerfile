FROM python:3.11-slim

WORKDIR /app

COPY api/ .

CMD ["python3", "-u", "server.py"]
