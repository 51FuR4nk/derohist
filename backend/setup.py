#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from setuptools import setup, find_packages

setup(
    name="backend",
    version="0.1.0",
    packages=find_packages(),
    install_requires=[
        "requests>=2.31",
        "mariadb>=1.1.10",
        "python-dotenv>=1.0",
    ],
    python_requires=">=3.9",
)
